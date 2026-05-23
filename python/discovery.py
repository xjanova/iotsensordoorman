"""
Bunny Door System — Discovery Module
====================================
Auto-pair บน WiFi เดียวกัน:
  1) Pi broadcast ตัวเองทุก 5 วินาที (UDP)
  2) Pi ฟัง broadcast จาก ESP32 และ device อื่น → register กับ web
  3) Pi subnet-scan หา web server อัตโนมัติ (ถ้าไม่ได้คอนฟิก URL)

Protocol packet (UDP, port 47474, broadcast 255.255.255.255):
    BUNNYDOOR|v1|<ROLE>|<DEVICE_ID>|<IP>|<PORT>|<TOKEN>
ตัวอย่าง:
    BUNNYDOOR|v1|PI|B8:27:EB:11:22:33|192.168.1.50|5000|a3f8...
"""
import socket
import struct
import threading
import time
import uuid
import json
import ipaddress
import logging
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed
from urllib import request as urlrequest, error as urlerror

DISCOVERY_PORT = 47474
DISCOVERY_MAGIC = "BUNNYDOOR"
DISCOVERY_VERSION = "v1"
BROADCAST_INTERVAL = 5  # seconds
WEB_RESCAN_INTERVAL = 300  # seconds — ตรวจสอบใหม่ทุก 5 นาทีถ้า web หาย

_log = logging.getLogger("discovery")


# ============================================================
# Utility
# ============================================================
def get_local_ip() -> str:
    """หา IP ของเครื่องที่ใช้คุยกับ internet"""
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        s.connect(("8.8.8.8", 80))
        return s.getsockname()[0]
    except Exception:
        return "127.0.0.1"
    finally:
        s.close()


def get_mac_address() -> str:
    """MAC address ในรูปแบบ AA:BB:CC:DD:EE:FF"""
    mac_int = uuid.getnode()
    return ":".join(f"{(mac_int >> i) & 0xff:02X}" for i in (40, 32, 24, 16, 8, 0))


def _broadcast_address(ip: str) -> str:
    """หา broadcast address ของ subnet — สมมติ /24"""
    parts = ip.split(".")
    if len(parts) != 4:
        return "255.255.255.255"
    return f"{parts[0]}.{parts[1]}.{parts[2]}.255"


# ============================================================
# Packet encode/decode
# ============================================================
def encode_packet(role: str, device_id: str, ip: str, port: int, token: str) -> bytes:
    parts = [DISCOVERY_MAGIC, DISCOVERY_VERSION, role.upper(), device_id, ip, str(port), token]
    return "|".join(parts).encode("utf-8")


def decode_packet(data: bytes) -> dict | None:
    """คืน dict หรือ None ถ้า packet ไม่ตรงรูปแบบ
       รองรับ 2 format:
         - role=PI/ESP32: MAGIC|v1|ROLE|device_id|ip|port|token         (7 fields)
         - role=WEB:      MAGIC|v1|WEB|device_id|ip|port|url_path|token (8 fields)
    """
    try:
        text = data.decode("utf-8", errors="ignore").strip()
        parts = text.split("|")
        if len(parts) < 7:
            return None
        if parts[0] != DISCOVERY_MAGIC or parts[1] != DISCOVERY_VERSION:
            return None
        role = parts[2].upper()
        if role == "WEB" and len(parts) >= 8:
            return {
                "role": role,
                "device_id": parts[3],
                "ip": parts[4],
                "port": int(parts[5]) if parts[5].isdigit() else 80,
                "url_path": parts[6],
                "token": parts[7],
            }
        return {
            "role": role,
            "device_id": parts[3],
            "ip": parts[4],
            "port": int(parts[5]) if parts[5].isdigit() else 0,
            "token": parts[6],
        }
    except Exception:
        return None


# ============================================================
# Web Discovery (subnet scan)
# ============================================================
def _ping_web(url: str, timeout: float = 1.5) -> dict | None:
    """ลองยิง GET <url>/api/pair/ping.php — คืน dict ถ้าใช่ web server ของเรา"""
    try:
        req = urlrequest.Request(url + "/api/pair/ping.php", headers={"User-Agent": "BunnyDoor-Pi/1.0"})
        with urlrequest.urlopen(req, timeout=timeout) as resp:
            if resp.status != 200:
                return None
            data = json.loads(resp.read().decode("utf-8"))
            if data.get("service") == "bunny-door-web":
                return data
    except (urlerror.URLError, json.JSONDecodeError, socket.timeout, ConnectionError, OSError):
        pass
    return None


def discover_web_url(local_ip: str, base_paths: list[str] | None = None) -> str | None:
    """Subnet scan หา web server บน /24
       คืน base URL เช่น http://192.168.1.10/bunny-door/web
       base_paths: ลำดับ path ที่ลอง — ลอง deep path ก่อน (กัน collision ที่ root)
    """
    if base_paths is None:
        # ลอง deep paths ก่อน — เผื่อ user clone full repo ที่มี web/ subfolder
        base_paths = [
            "/bunny-door/web",       # full repo clone (พบบ่อยสุด)
            "/iotsensordoorman/web", # github default folder name
            "/bunny-door",           # web-only clone
            "/iotsensordoorman",
            "/web",
            "",                       # web ที่ document root
        ]

    try:
        net = ipaddress.ip_network(local_ip + "/24", strict=False)
    except ValueError:
        return None

    candidates: list[str] = []
    for host in net.hosts():
        for path in base_paths:
            candidates.append(f"http://{host}{path}")

    _log.info(f"[discover-web] scanning {len(candidates)} candidates on subnet {net}")

    found: str | None = None
    with ThreadPoolExecutor(max_workers=50) as ex:
        futures = {ex.submit(_ping_web, url): url for url in candidates}
        for fut in as_completed(futures):
            if found:
                continue
            data = fut.result()
            if data:
                found = futures[fut]
                _log.info(f"[discover-web] FOUND: {found}")
                # ไม่ break ทันที — ปล่อย thread ที่เหลือเสร็จเอง (จะถูก cancel ตอน executor exit)
                break

    return found


# ============================================================
# DeviceRegistry — เก็บ device ที่ discover ได้
# ============================================================
class DeviceRegistry:
    """In-memory cache ของอุปกรณ์ที่เจอ + callback ตอน update"""

    def __init__(self):
        self._lock = threading.Lock()
        self._devices: dict[tuple[str, str], dict] = {}  # (role, device_id) → info
        self._on_update_cb = None

    def on_update(self, cb):
        self._on_update_cb = cb

    def update(self, role: str, device_id: str, ip: str, port: int, **extra):
        key = (role.upper(), device_id)
        now = time.time()
        with self._lock:
            existing = self._devices.get(key)
            info = {
                "role": role.upper(),
                "device_id": device_id,
                "ip": ip,
                "port": port,
                "last_seen": now,
                "first_seen": existing["first_seen"] if existing else now,
                **extra,
            }
            self._devices[key] = info
            is_new = existing is None
            ip_changed = existing and existing.get("ip") != ip
        if (is_new or ip_changed) and self._on_update_cb:
            try:
                self._on_update_cb(info, is_new=is_new)
            except Exception as e:
                _log.error(f"[registry] on_update callback error: {e}")

    def get(self, role: str, device_id: str | None = None):
        with self._lock:
            if device_id:
                return self._devices.get((role.upper(), device_id))
            return [v for (r, _), v in self._devices.items() if r == role.upper()]

    def all(self):
        with self._lock:
            return list(self._devices.values())


# ============================================================
# DiscoveryService — รวมทุกอย่างเข้าด้วยกัน
# ============================================================
class DiscoveryService:
    """
    เริ่ม UDP broadcast + listener + web discovery
    ใช้:
        svc = DiscoveryService(role="PI", port=5000, token="abc123", web_url=None)
        svc.registry.on_update(my_callback)
        svc.start()
    """

    def __init__(self, role: str, port: int, token: str,
                 web_url: str | None = None,
                 device_id: str | None = None):
        self.role = role.upper()
        self.port = port
        self.token = token
        self.device_id = device_id or get_mac_address()
        self.registry = DeviceRegistry()
        self._stop_event = threading.Event()
        self._web_url = web_url
        self._web_url_lock = threading.Lock()

    def get_web_url(self) -> str | None:
        with self._web_url_lock:
            return self._web_url

    def set_web_url(self, url: str):
        with self._web_url_lock:
            self._web_url = url

    def stop(self):
        self._stop_event.set()

    def start(self):
        threading.Thread(target=self._broadcaster_loop, name="discovery-broadcast", daemon=True).start()
        threading.Thread(target=self._listener_loop,    name="discovery-listen",    daemon=True).start()
        threading.Thread(target=self._web_loop,         name="discovery-web",       daemon=True).start()
        _log.info(f"[discovery] started — role={self.role} id={self.device_id} token=***{self.token[-4:] if self.token else '?'}")

    # --------------- broadcast ---------------
    def _broadcaster_loop(self):
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        try:
            sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        except OSError:
            pass

        while not self._stop_event.is_set():
            try:
                local_ip = get_local_ip()
                pkt = encode_packet(self.role, self.device_id, local_ip, self.port, self.token)
                bcast = _broadcast_address(local_ip)
                sock.sendto(pkt, (bcast, DISCOVERY_PORT))
                # ส่งซ้ำที่ 255.255.255.255 ด้วย (กรณี subnet mask ไม่ใช่ /24)
                try:
                    sock.sendto(pkt, ("255.255.255.255", DISCOVERY_PORT))
                except OSError:
                    pass
            except Exception as e:
                _log.warning(f"[broadcast] error: {e}")
            self._stop_event.wait(BROADCAST_INTERVAL)

        sock.close()

    # --------------- listener ---------------
    def _listener_loop(self):
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        try:
            sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        except OSError:
            pass
        try:
            sock.bind(("", DISCOVERY_PORT))
        except OSError as e:
            _log.error(f"[listen] cannot bind to {DISCOVERY_PORT}: {e}")
            return
        sock.settimeout(1.0)

        while not self._stop_event.is_set():
            try:
                data, addr = sock.recvfrom(1024)
            except socket.timeout:
                continue
            except OSError:
                break

            pkt = decode_packet(data)
            if not pkt:
                continue
            # ละทิ้ง broadcast จากตัวเอง
            if pkt["role"] == self.role and pkt["device_id"] == self.device_id:
                continue
            # ตรวจ token — ถ้า "ทั้งคู่" มี token ตั้งไว้ แต่ไม่ตรง → ข้าม
            # ถ้าตัวเองหรือ packet มี token ว่าง → ยอมรับ (zero-config mode)
            if self.token and pkt["token"] and pkt["token"] != self.token:
                _log.debug(f"[listen] token mismatch from {addr[0]} role={pkt['role']}")
                continue
            # ใช้ source IP จาก socket (น่าเชื่อกว่า field ใน packet ในกรณี NAT)
            real_ip = addr[0] if pkt["ip"] != addr[0] else pkt["ip"]

            # WEB role — ใช้ url_path ที่ web ส่งมา set web_url ทันที (ข้าม subnet scan)
            if pkt["role"] == "WEB" and pkt.get("url_path") is not None:
                port = pkt["port"] or 80
                url_path = pkt["url_path"]
                # ใส่ http:// + IP + path
                if port == 80:
                    web_url = f"http://{real_ip}{url_path}"
                else:
                    web_url = f"http://{real_ip}:{port}{url_path}"
                current = self.get_web_url()
                if current != web_url:
                    _log.info(f"[listen] WEB announced: {web_url} (replaced {current})")
                    self.set_web_url(web_url)
                continue

            self.registry.update(pkt["role"], pkt["device_id"], real_ip, pkt["port"])

        sock.close()

    # --------------- web discovery ---------------
    def _web_loop(self):
        """หา web URL ทุกครั้งที่ยังไม่มี หรือเช็คเป็นระยะ"""
        while not self._stop_event.is_set():
            url = self.get_web_url()
            if url and _ping_web(url, timeout=2.0):
                # ใช้งานได้ — รอนานหน่อย
                self._stop_event.wait(WEB_RESCAN_INTERVAL)
                continue

            # ลอง discover ใหม่
            local_ip = get_local_ip()
            found = discover_web_url(local_ip)
            if found:
                self.set_web_url(found)
                _log.info(f"[discovery] web URL set: {found}")
            else:
                _log.warning("[discovery] web server not found — retry in 30s")
                self._stop_event.wait(30)
