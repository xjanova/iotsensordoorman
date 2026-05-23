#!/usr/bin/env bash
# ============================================================
# Bunny Door System - Pi Installation Diagnostic
# ============================================================
# Usage:
#   cd ~/bunny-door/src && git pull && bash python/check-pi.sh
#
# ตรวจสภาพการติดตั้งบน Raspberry Pi ทุกชั้น:
#   ระบบ, โฟลเดอร์, venv, Python deps, systemd, port, กล้อง, log, API
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ok()   { echo -e "${GREEN}✔${NC} $*"; }
bad()  { echo -e "${RED}✘${NC} $*"; }
warn() { echo -e "${YELLOW}⚠${NC} $*"; }
hdr()  { echo -e "\n${BLUE}── $* ──${NC}"; }

PROJECT_DIR="$HOME/bunny-door/src"
VENV_DIR="$HOME/bunny-door/venv"

echo -e "${BLUE}"
echo "═══════════════════════════════════════════════════════"
echo "  BUNNY DOOR — Pi Installation Diagnostic"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "═══════════════════════════════════════════════════════"
echo -e "${NC}"

# ─────────────────────────────────────────────────────────────
hdr "[1] ระบบ"
echo "Hostname : $(hostname)"
echo "Pi IP    : $(hostname -I | xargs)"
echo "OS       : $(grep PRETTY /etc/os-release | cut -d'\"' -f2)"
echo "Arch     : $(uname -m)"
echo "RAM      : $(free -h | awk '/Mem/{print $2 " total, " $7 " avail"}')"
echo "Swap     : $(free -h | awk '/Swap/{print $2}')"
echo "Disk /   : $(df -h / | awk 'NR==2{print $4 " free"}')"
echo "User     : $USER (groups: $(groups))"

# ─────────────────────────────────────────────────────────────
hdr "[2] โฟลเดอร์โปรเจกต์"
for d in "$HOME/bunny-door" "$PROJECT_DIR" "$PROJECT_DIR/python" "$VENV_DIR" "$PROJECT_DIR/python/images" "$PROJECT_DIR/python/snapshots"; do
    if [ -e "$d" ]; then ok "$d"; else bad "$d (ไม่มี)"; fi
done

# ─────────────────────────────────────────────────────────────
hdr "[3] ไฟล์สำคัญ"
for f in "$PROJECT_DIR/python/.env" \
         "$PROJECT_DIR/python/face_server.py" \
         "$PROJECT_DIR/python/discovery.py" \
         "$PROJECT_DIR/python/config.py" \
         "$PROJECT_DIR/python/requirements.txt"; do
    if [ -f "$f" ]; then ok "$(basename "$f")"; else bad "$(basename "$f") (ไม่มี)"; fi
done

# ─────────────────────────────────────────────────────────────
hdr "[4] เนื้อหา .env (ซ่อนความลับ)"
if [ -f "$PROJECT_DIR/python/.env" ]; then
    grep -v "^#" "$PROJECT_DIR/python/.env" | grep -v "^$" | \
        sed -E 's/(PASSWORD=).+/\1***/' | \
        sed -E 's/(TOKEN=).+/\1***/'
else
    bad "ไม่มีไฟล์ .env — ยังไม่ได้รัน install-pi.sh ให้จบ"
fi

# ─────────────────────────────────────────────────────────────
hdr "[5] venv + Python packages"
if [ -d "$VENV_DIR" ] && [ -x "$VENV_DIR/bin/python" ]; then
    ok "venv: $VENV_DIR"
    echo "Python: $($VENV_DIR/bin/python --version)"
    echo ""
    "$VENV_DIR/bin/python" - <<'PYEOF'
import importlib
mods = [
    'dlib', 'face_recognition', 'cv2',
    'flask', 'flask_cors',
    'mysql.connector', 'requests', 'PIL', 'numpy'
]
for m in mods:
    try:
        v = importlib.import_module(m)
        ver = getattr(v, '__version__', '?')
        print(f"  ✔ {m:25s} {ver}")
    except Exception as e:
        print(f"  ✘ {m:25s} {e}")
PYEOF
else
    bad "venv ไม่มี — ยังไม่ได้ติดตั้ง Python packages"
fi

# ─────────────────────────────────────────────────────────────
hdr "[6] systemd service: bunny-door"
if systemctl list-unit-files 2>/dev/null | grep -q "^bunny-door"; then
    ok "Service file: มี"
    echo "  Status   : $(systemctl is-active bunny-door)"
    echo "  Enabled  : $(systemctl is-enabled bunny-door 2>/dev/null)"
    echo "  ExecStart: $(systemctl cat bunny-door 2>/dev/null | grep ^ExecStart= | cut -d= -f2-)"
else
    bad "Service ยังไม่ถูกสร้าง — install-pi.sh ทำไม่ครบ step 8"
fi

# ─────────────────────────────────────────────────────────────
hdr "[7] Port 5000 (face_server)"
if ss -tlnp 2>/dev/null | grep -q ":5000"; then
    ok "มี process ฟัง :5000"
    ss -tlnp 2>/dev/null | grep ":5000"
else
    bad "ไม่มี process ฟัง :5000"
fi

# ─────────────────────────────────────────────────────────────
hdr "[8] UDP 47474 (discovery)"
if ss -ulnp 2>/dev/null | grep -q ":47474"; then
    ok "มี process ฟัง UDP :47474 (discovery กำลังทำงาน)"
else
    warn "ไม่มี process ฟัง UDP :47474 — discovery ยังไม่ทำงาน"
fi

# ─────────────────────────────────────────────────────────────
hdr "[9] กล้อง USB"
if ls /dev/video* 2>/dev/null; then
    ok "เจอกล้อง"
else
    warn "ไม่เจอ /dev/video* — เสียบกล้อง USB หรือยัง?"
fi
groups | grep -qw video && ok "user อยู่ในกลุ่ม video" || warn "user ไม่อยู่ในกลุ่ม video — ต้อง: sudo usermod -aG video $USER && reboot"

# ─────────────────────────────────────────────────────────────
hdr "[10] log บริการล่าสุด (15 บรรทัด)"
if systemctl list-unit-files 2>/dev/null | grep -q "^bunny-door"; then
    sudo journalctl -u bunny-door -n 15 --no-pager 2>/dev/null
else
    warn "(ข้าม — ไม่มี service)"
fi

# ─────────────────────────────────────────────────────────────
hdr "[11] ทดสอบ API บนเครื่อง Pi เอง"
STATUS_JSON=$(curl -s -m 3 http://localhost:5000/api/status 2>/dev/null)
if [ -n "$STATUS_JSON" ]; then
    ok "/api/status ตอบ:"
    echo "$STATUS_JSON" | head -c 400
    echo ""
else
    bad "/api/status ไม่ตอบ — face_server ไม่ได้รัน"
fi

# ─────────────────────────────────────────────────────────────
hdr "[12] ทดสอบ Discovery — หา Web Server บน subnet"
if [ -d "$VENV_DIR" ] && [ -f "$PROJECT_DIR/python/discovery.py" ]; then
    cd "$PROJECT_DIR/python"
    timeout 30 "$VENV_DIR/bin/python" - <<'PYEOF' 2>&1 | tail -5
try:
    from discovery import discover_web_url, get_local_ip
    ip = get_local_ip()
    print(f"  Pi local IP : {ip}")
    print(f"  Scanning {ip[:ip.rfind('.')]}.0/24 ...")
    url = discover_web_url(ip)
    if url:
        print(f"  ✔ พบ Web ที่: {url}")
    else:
        print(f"  ✘ ไม่พบ Web server บน subnet")
        print(f"    ตรวจว่า Laragon รันอยู่ + /bunny-door/api/pair/ping.php ตอบ")
except Exception as e:
    print(f"  ✘ Error: {e}")
PYEOF
else
    warn "(ข้าม — venv หรือ discovery.py ไม่มี)"
fi

# ─────────────────────────────────────────────────────────────
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo "  จบการตรวจสอบ — ส่ง screenshot/output ให้ Claude"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"

# ─────────────────────────────────────────────────────────────
# สรุปสั้น: ขั้นต่อไปต้องทำอะไร
hdr "สรุป — ขั้นต่อไปต้องทำอะไร"

PROBLEMS=0

if [ ! -d "$VENV_DIR" ]; then
    bad "ยังไม่ได้ติดตั้ง venv → รัน: bash $PROJECT_DIR/python/install-pi.sh"
    PROBLEMS=$((PROBLEMS+1))
fi

if ! systemctl list-unit-files 2>/dev/null | grep -q "^bunny-door"; then
    bad "ยังไม่มี systemd service → รัน install-pi.sh ให้จบ"
    PROBLEMS=$((PROBLEMS+1))
elif ! systemctl is-active --quiet bunny-door 2>/dev/null; then
    bad "Service ไม่รัน → sudo systemctl start bunny-door && sudo journalctl -u bunny-door -n 50"
    PROBLEMS=$((PROBLEMS+1))
fi

if [ -f "$PROJECT_DIR/python/.env" ]; then
    if ! grep -q "^PAIRING_TOKEN=.\+" "$PROJECT_DIR/python/.env"; then
        warn "PAIRING_TOKEN ใน .env ว่าง → copy token จาก web (network.php) แล้ว: nano $PROJECT_DIR/python/.env"
        PROBLEMS=$((PROBLEMS+1))
    fi
fi

if [ $PROBLEMS -eq 0 ]; then
    ok "ทุกอย่างพร้อม — เปิด web network.php รอ ~10s ดูว่ามี Pi เข้ามา PENDING มั้ย"
fi
