"""
Bunny Door Web Announcer
========================
รันบน PC (Laragon เครื่อง) — broadcast UDP บอก Pi/ESP32 ว่า web อยู่ตรงไหน
แทนการให้ Pi ต้อง subnet-scan หา web เอง (ช้า + เดา path)

วิธีรัน (Windows PowerShell):
    python web/announcer.py
หรือ autostart ผ่าน Laragon "Auto Start" หรือ Windows Task Scheduler

Packet format (UDP 47474):
    BUNNYDOOR|v1|WEB|<hostname>|<ip>|80|<full_url_path>|<token>

หมายเหตุ: token เลือกอ่านจาก settings.pairing_token ใน MySQL
ถ้า MySQL ไม่ตอบก็จะส่ง token ว่าง (zero-config mode)
"""
import os
import sys
import socket
import time
import uuid
import argparse
from pathlib import Path

DISCOVERY_PORT = 47474
MAGIC = "BUNNYDOOR"
VERSION = "v1"
BROADCAST_INTERVAL = 5  # seconds


def get_local_ip() -> str:
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        s.connect(("8.8.8.8", 80))
        return s.getsockname()[0]
    except Exception:
        return "127.0.0.1"
    finally:
        s.close()


def broadcast_address(ip: str) -> str:
    parts = ip.split(".")
    if len(parts) != 4:
        return "255.255.255.255"
    return f"{parts[0]}.{parts[1]}.{parts[2]}.255"


def get_pairing_token_from_db(db_config: dict) -> str:
    """ลองอ่าน pairing_token จาก MySQL — ถ้าอ่านไม่ได้คืน '' (zero-config mode)"""
    try:
        import mysql.connector
        cnx = mysql.connector.connect(
            host=db_config.get("host", "127.0.0.1"),
            port=db_config.get("port", 3306),
            user=db_config.get("user", "root"),
            password=db_config.get("password", ""),
            database=db_config.get("name", "bunny_door"),
            connection_timeout=3,
        )
        cur = cnx.cursor()
        cur.execute("SELECT setting_value FROM settings WHERE setting_key='pairing_token' LIMIT 1")
        row = cur.fetchone()
        cur.close()
        cnx.close()
        return row[0] if row else ""
    except Exception:
        return ""


def auto_detect_url_path() -> str:
    """พยายามเดา URL path จาก script location
       เช่น script อยู่ที่ C:\\laragon\\www\\bunny-door\\web\\announcer.py
       → URL path = /bunny-door/web
    """
    try:
        script_dir = Path(__file__).resolve().parent  # .../bunny-door/web
        # หา www root (parent ที่ชื่อ www หรือ htdocs)
        for parent in script_dir.parents:
            if parent.name.lower() in ("www", "htdocs", "public_html"):
                # path จาก www root ถึง script_dir
                rel = script_dir.relative_to(parent)
                return "/" + str(rel).replace("\\", "/")
        # fallback: ใช้ parent name 2 ชั้น
        return f"/{script_dir.parent.name}/{script_dir.name}"
    except Exception:
        return ""


def main():
    parser = argparse.ArgumentParser(description="Bunny Door Web Announcer")
    parser.add_argument("--path", default=None,
                        help="URL path ของ web (default: auto-detect)")
    parser.add_argument("--port", type=int, default=80,
                        help="HTTP port ของ Apache (default: 80)")
    parser.add_argument("--db-host", default="127.0.0.1")
    parser.add_argument("--db-port", type=int, default=3306)
    parser.add_argument("--db-user", default="root")
    parser.add_argument("--db-password", default="")
    parser.add_argument("--db-name", default="bunny_door")
    parser.add_argument("--interval", type=int, default=BROADCAST_INTERVAL)
    args = parser.parse_args()

    url_path = args.path or auto_detect_url_path()
    if not url_path:
        url_path = "/bunny-door/web"

    db_cfg = {
        "host": args.db_host,
        "port": args.db_port,
        "user": args.db_user,
        "password": args.db_password,
        "name": args.db_name,
    }

    hostname = socket.gethostname()
    device_id = hostname  # ใช้ hostname เป็น device_id (PC ไม่ค่อยมี MAC ที่ stable)

    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
    try:
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    except OSError:
        pass

    print(f"[Announcer] hostname={hostname}")
    print(f"[Announcer] URL path={url_path}")
    print(f"[Announcer] broadcasting on UDP {DISCOVERY_PORT} every {args.interval}s")
    print(f"[Announcer] press Ctrl+C to stop\n")

    last_token = None
    while True:
        try:
            ip = get_local_ip()
            token = get_pairing_token_from_db(db_cfg) if (time.time() // 60) % 5 == 0 else last_token
            # อ่าน token ทุกๆ 5 นาที (กรณี admin regenerate token)
            if token is None:
                token = ""
            last_token = token

            packet = f"{MAGIC}|{VERSION}|WEB|{device_id}|{ip}|{args.port}|{url_path}|{token}"
            bcast = broadcast_address(ip)
            sock.sendto(packet.encode("utf-8"), (bcast, DISCOVERY_PORT))
            try:
                sock.sendto(packet.encode("utf-8"), ("255.255.255.255", DISCOVERY_PORT))
            except OSError:
                pass

            ts = time.strftime("%H:%M:%S")
            print(f"[{ts}] → {bcast}:{DISCOVERY_PORT}  url=http://{ip}:{args.port}{url_path}  token={'***'+token[-4:] if token else '(none)'}")
        except KeyboardInterrupt:
            print("\n[Announcer] stopped")
            break
        except Exception as e:
            print(f"[Announcer] error: {e}")

        time.sleep(args.interval)

    sock.close()


if __name__ == "__main__":
    main()
