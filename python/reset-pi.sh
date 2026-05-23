#!/usr/bin/env bash
# ============================================================
# Bunny Door - Reset Pi to Zero-Config Mode
# ============================================================
# ใช้สำหรับเครื่องที่เคยติดตั้งแบบเก่า (กรอก DB_HOST/PAIRING_TOKEN เอง)
# แล้วต้องการย้ายมาใช้ zero-config plug-and-play
#
# Usage:
#   cd ~/bunny-door/src && git pull && bash python/reset-pi.sh
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'
ok()   { echo -e "${GREEN}✔${NC} $*"; }
warn() { echo -e "${YELLOW}⚠${NC} $*"; }
hdr()  { echo -e "\n${BLUE}── $* ──${NC}"; }

ENV_FILE="$HOME/bunny-door/src/python/.env"

# Detect camera IDs from existing .env (เพื่อ preserve)
CAM_OUT=0
CAM_IN=-1
CAM_MODE=always
if [ -f "$ENV_FILE" ]; then
    CAM_OUT=$(grep -E "^CAMERA_OUTSIDE_ID=" "$ENV_FILE" | cut -d= -f2 || echo 0)
    CAM_IN=$(grep -E "^CAMERA_INSIDE_ID=" "$ENV_FILE" | cut -d= -f2 || echo -1)
    CAM_MODE=$(grep -E "^CAMERA_MODE=" "$ENV_FILE" | cut -d= -f2 || echo always)
fi
CAM_OUT=${CAM_OUT:-0}
CAM_IN=${CAM_IN:--1}
CAM_MODE=${CAM_MODE:-always}

hdr "[1] Backup .env เก่า"
if [ -f "$ENV_FILE" ]; then
    BAK="$ENV_FILE.bak.$(date +%Y%m%d-%H%M%S)"
    cp "$ENV_FILE" "$BAK"
    ok "backup → $BAK"
else
    warn "ไม่มี .env เก่า — สร้างใหม่"
fi

hdr "[2] เขียน .env แบบ Zero-Config"
cat > "$ENV_FILE" <<EOF
# Zero-Config — ทุกค่าจะถูก resolve อัตโนมัติผ่าน UDP discovery + admin approve
# Generated $(date)

# DB — host/user/pass จะถูกดึงจาก web ผ่าน /api/pair/credentials.php
DB_HOST=
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=bunny_door

# ESP32 — จะถูกอัปเดตเมื่อ ESP32 broadcast UDP มา
ESP32_IP=

# Cameras (preserved จาก .env เก่า)
CAMERA_OUTSIDE_ID=$CAM_OUT
CAMERA_INSIDE_ID=$CAM_IN
CAMERA_MODE=$CAM_MODE

# Web Server — discovery subnet-scan หาเอง
WEB_SERVER_URL=

# Auto-pair — token ดึงจาก /api/pair/credentials.php
PAIRING_TOKEN=
DISCOVERY_ENABLED=1
EOF
chmod 600 "$ENV_FILE"
ok "เขียน .env ใหม่ (camera_out=$CAM_OUT camera_in=$CAM_IN mode=$CAM_MODE)"

hdr "[3] ตั้ง sudoers ให้ service อ่าน WiFi credentials ได้"
sudo tee /etc/sudoers.d/bunny-door-wifi > /dev/null <<EOF
# Bunny Door: ให้ service user อ่าน WiFi password ของ Pi ได้
${USER:-pi} ALL=(root) NOPASSWD: /usr/bin/nmcli -s -g 802-11-wireless-security.psk connection show *
${USER:-pi} ALL=(root) NOPASSWD: /bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
${USER:-pi} ALL=(root) NOPASSWD: /usr/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
EOF
sudo chmod 0440 /etc/sudoers.d/bunny-door-wifi
ok "sudoers พร้อม — Pi อ่าน WiFi password ได้แล้ว"

hdr "[4] Restart service"
sudo systemctl restart bunny-door
sleep 3
if sudo systemctl is-active --quiet bunny-door; then
    ok "Service active"
else
    warn "Service ยังไม่ active — ดู log ต่อข้างล่าง"
fi

hdr "[4] log ล่าสุด 20 บรรทัด"
sudo journalctl -u bunny-door -n 20 --no-pager

hdr "[5] ตรวจ port"
ss -tlnp 2>/dev/null | grep ":5000" && ok "Port 5000 (Flask) listening" || warn "Port 5000 ยังไม่ขึ้น (อาจรออีก ~5s)"
ss -ulnp 2>/dev/null | grep ":47474" && ok "UDP 47474 (Discovery) listening" || warn "UDP 47474 ยังไม่ขึ้น"

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo "  เสร็จแล้ว — Pi อยู่ใน Zero-Config Mode"
echo ""
echo "  ขั้นต่อไป:"
echo "    1. เปิด web → http://<PC_IP>/bunny-door/network.php"
echo "    2. รอ ~30 วินาที จะเห็น Pi เครื่องนี้ขึ้นมาเป็น PENDING"
echo "    3. กด 'อนุมัติ' → Pi จะดึง DB creds อัตโนมัติ"
echo ""
echo "  ดู log สด:"
echo "    sudo journalctl -u bunny-door -f"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
