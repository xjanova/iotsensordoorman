#!/usr/bin/env bash
# ============================================================
# Bunny Door System - Raspberry Pi Auto Installer
# ============================================================
# Usage:
#   git clone https://github.com/xjanova/iotsensordoorman.git ~/bunny-door/src
#   cd ~/bunny-door/src && bash python/install-pi.sh
#
# What this does:
#   1. apt update + install build deps (cmake, dlib prereqs, v4l-utils)
#   2. Expand swap to 2GB (for dlib compile)
#   3. Create venv + pip install -r requirements.txt
#   4. Restore swap to 512MB
#   5. Generate python/.env (asks for PC IP, DB password, ESP32 IP)
#   6. Test connection to Laragon MySQL on PC
#   7. Create systemd service "bunny-door"
#   8. Enable + start service
#
# DB lives on PC (Laragon). Pi connects back to PC's MySQL.
# This script does NOT install MariaDB on Pi.
# ============================================================

set -e
trap 'on_error $? $LINENO' ERR

# ── Colors ──────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

STEP=0
TOTAL=8
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/.." && pwd )"
VENV_DIR="$HOME/bunny-door/venv"
LOG_FILE="$HOME/bunny-door-install.log"

log()    { echo -e "${BLUE}[$(date +%H:%M:%S)]${NC} $*" | tee -a "$LOG_FILE"; }
ok()     { echo -e "${GREEN}✔${NC} $*" | tee -a "$LOG_FILE"; }
warn()   { echo -e "${YELLOW}⚠${NC} $*" | tee -a "$LOG_FILE"; }
err()    { echo -e "${RED}✘${NC} $*" | tee -a "$LOG_FILE"; }
step()   { STEP=$((STEP+1)); echo -e "\n${BLUE}━━━ [${STEP}/${TOTAL}] $*${NC}" | tee -a "$LOG_FILE"; }

on_error() {
    err "Failed at line $2 with exit code $1"
    err "ดู log เต็มที่: $LOG_FILE"
    exit "$1"
}

# ── Preflight ───────────────────────────────────────────────
echo -e "${BLUE}"
echo "╔══════════════════════════════════════════════════════════╗"
echo "║   Bunny Door System - Raspberry Pi Auto Installer       ║"
echo "║   Project: $PROJECT_DIR"
echo "╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check OS arch
if [[ "$(uname -m)" != "aarch64" && "$(uname -m)" != "armv7l" ]]; then
    warn "ไม่ใช่ Raspberry Pi (arch: $(uname -m)) — ยังต่อได้ ถ้าแน่ใจ"
    read -rp "ต่อหรือไม่? [y/N] " yn
    [[ "$yn" == "y" || "$yn" == "Y" ]] || exit 1
fi

# Check sudo
if ! sudo -n true 2>/dev/null; then
    log "ต้อง sudo — กรุณาใส่รหัสผ่าน"
    sudo true
fi

# Check requirements.txt exists
if [[ ! -f "$SCRIPT_DIR/requirements.txt" ]]; then
    err "ไม่พบ $SCRIPT_DIR/requirements.txt — รันสคริปต์ใน repo ที่ clone มาเท่านั้น"
    exit 1
fi

> "$LOG_FILE"
log "เริ่มติดตั้ง — log ที่ $LOG_FILE"

# ── Zero-Config ─────────────────────────────────────────────
# ไม่ถามอะไร — Pi จะหา web/DB เองผ่าน UDP discovery + subnet scan
# ค่าทั้งหมดเป็น default ที่ทำงานกับ Laragon (user=root, pass ว่าง)
# Pi จะเอา credentials จริงจาก /api/pair/credentials.php หลัง admin approve
PC_IP=""            # จะถูก auto-discover
DB_PASS=""          # default Laragon = ว่าง
ESP32_IP=""         # ESP32 จะ broadcast IP มาเอง
CAM_OUT=0
CAM_IN=-1
PAIRING_TOKEN=""    # ใช้ auto_pairing แทน
RUN_USER="$USER"

echo -e "\n${YELLOW}── Zero-Config Install ──${NC}"
echo "  ไม่ต้องกรอกอะไร — ระบบจะหา web/DB/ESP32 เองผ่าน UDP discovery"
echo "  หลังติดตั้งเสร็จ:"
echo "    1. เปิดเว็บ admin (/network.php) จะเห็น Pi ขึ้นมาเป็น PENDING"
echo "    2. กด 'อนุมัติ' → Pi ดึง DB credentials อัตโนมัติ → พร้อมใช้งาน"
echo ""
echo "  Service user:           $RUN_USER"
echo "  Project dir:            $PROJECT_DIR"
echo "  Venv dir:               $VENV_DIR"
sleep 2

START_TS=$(date +%s)

# ── Step 1: apt update + install ────────────────────────────
step "อัปเดตระบบ + ติดตั้ง Build Dependencies"
sudo apt update -y >> "$LOG_FILE" 2>&1
sudo apt upgrade -y >> "$LOG_FILE" 2>&1
sudo apt install -y \
    build-essential cmake gfortran git pkg-config \
    python3-dev python3-pip python3-venv \
    libjpeg-dev libpng-dev libtiff-dev \
    libavcodec-dev libavformat-dev libswscale-dev \
    libv4l-dev v4l-utils \
    liblapack-dev libblas-dev libopenblas-dev \
    libboost-all-dev \
    libgtk-3-dev libxvidcore-dev libx264-dev \
    libhdf5-dev libhdf5-serial-dev \
    mariadb-client \
    fonts-thai-tlwg fonts-noto-cjk fonts-noto-color-emoji \
    curl >> "$LOG_FILE" 2>&1
ok "Dependencies ติดตั้งเรียบร้อย (รวม Thai fonts)"

# ── Step 2: Expand swap ─────────────────────────────────────
step "ขยาย Swap เป็น 2GB (สำหรับ compile dlib)"
CURRENT_SWAP=$(grep -E "^CONF_SWAPSIZE=" /etc/dphys-swapfile | cut -d= -f2)
if [[ "$CURRENT_SWAP" -lt 2048 ]]; then
    sudo dphys-swapfile swapoff >> "$LOG_FILE" 2>&1
    sudo sed -i 's/^CONF_SWAPSIZE=.*/CONF_SWAPSIZE=2048/' /etc/dphys-swapfile
    sudo dphys-swapfile setup >> "$LOG_FILE" 2>&1
    sudo dphys-swapfile swapon >> "$LOG_FILE" 2>&1
    ok "Swap = $(free -h | awk '/Swap/{print $2}')"
else
    ok "Swap มีอยู่แล้ว: $(free -h | awk '/Swap/{print $2}')"
fi

# ── Step 3: Create venv + pip install ───────────────────────
step "สร้าง venv + ติดตั้ง Python packages (~45-90 นาที)"
mkdir -p "$(dirname "$VENV_DIR")"
if [[ ! -d "$VENV_DIR" ]]; then
    python3 -m venv "$VENV_DIR"
    ok "สร้าง venv ที่ $VENV_DIR"
fi
# shellcheck disable=SC1091
source "$VENV_DIR/bin/activate"
pip install --upgrade pip setuptools wheel >> "$LOG_FILE" 2>&1
log "กำลังติดตั้งจาก requirements.txt (dlib ใช้เวลานานที่สุด อย่าปิดเครื่อง)"
pip install -r "$SCRIPT_DIR/requirements.txt" 2>&1 | tee -a "$LOG_FILE" | grep -E "Collecting|Successfully|ERROR" || true

# Verify imports
python3 -c "import dlib, face_recognition, cv2, flask, mysql.connector; print('all OK')" || {
    err "Python import ล้มเหลว — ดู log: $LOG_FILE"
    exit 1
}
ok "Python packages ติดตั้งเรียบร้อย"

# ── Step 4: Restore swap ────────────────────────────────────
step "คืน Swap เป็น 512MB (ยืดอายุ SD Card)"
sudo dphys-swapfile swapoff >> "$LOG_FILE" 2>&1
sudo sed -i 's/^CONF_SWAPSIZE=.*/CONF_SWAPSIZE=512/' /etc/dphys-swapfile
sudo dphys-swapfile setup >> "$LOG_FILE" 2>&1
sudo dphys-swapfile swapon >> "$LOG_FILE" 2>&1
# tune swappiness
echo "vm.swappiness=10" | sudo tee /etc/sysctl.d/99-bunny-door.conf >> "$LOG_FILE"
sudo sysctl -p /etc/sysctl.d/99-bunny-door.conf >> "$LOG_FILE" 2>&1
ok "Swap = $(free -h | awk '/Swap/{print $2}'), swappiness=10"

# ── Step 5: Generate .env ───────────────────────────────────
step "สร้าง python/.env"
ENV_FILE="$SCRIPT_DIR/.env"
if [[ -f "$ENV_FILE" ]]; then
    cp "$ENV_FILE" "$ENV_FILE.bak.$(date +%s)"
    warn "พบ .env เก่า — backup เป็น $ENV_FILE.bak.*"
fi
cat > "$ENV_FILE" <<EOF
# Auto-generated by install-pi.sh on $(date)
# ── Zero-Config — ค่าทั้งหมดจะถูกอัปเดตอัตโนมัติหลัง admin approve Pi ใน web UI ──

# DB — host จะถูก resolve จาก discovery; user/pass default Laragon
DB_HOST=
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=bunny_door

# ESP32 — จะถูกอัปเดตเมื่อ ESP32 broadcast UDP มา
ESP32_IP=

# Cameras (-1 = ปิด)
CAMERA_OUTSIDE_ID=$CAM_OUT
CAMERA_INSIDE_ID=$CAM_IN
CAMERA_MODE=always

# Web Server — ปล่อยว่าง ให้ discovery subnet-scan หาเอง
WEB_SERVER_URL=

# Auto-pair — token จะถูกดึงจาก /api/pair/credentials.php หลัง approve
PAIRING_TOKEN=
DISCOVERY_ENABLED=1
EOF
chmod 600 "$ENV_FILE"
ok "สร้าง $ENV_FILE"

# ── Step 6: (เว้น — ไม่ test DB เพราะ zero-config) ──────────
step "ข้าม — Zero-Config ไม่ test DB ที่นี่ (Pi จะ resolve เองตอนรัน)"
ok "ข้าม"

# ── Step 7: Create folders + permissions + sudoers (WiFi readout) ──
step "สร้างโฟลเดอร์ที่จำเป็น + sudoers สำหรับอ่าน WiFi config"
mkdir -p "$SCRIPT_DIR/images" "$SCRIPT_DIR/snapshots"
# Allow user to access cameras
if ! groups "$RUN_USER" | grep -q "\bvideo\b"; then
    sudo usermod -aG video "$RUN_USER"
    warn "เพิ่ม $RUN_USER เข้ากลุ่ม video — ต้อง reboot 1 ครั้งหลังติดตั้งเสร็จ"
fi
# sudoers สำหรับอ่าน WiFi password + restart service จาก UI
sudo tee /etc/sudoers.d/bunny-door > /dev/null <<EOF
# Bunny Door: NOPASSWD เฉพาะ commands ที่จำเป็น
$RUN_USER ALL=(root) NOPASSWD: /usr/bin/nmcli -s -g 802-11-wireless-security.psk connection show *
$RUN_USER ALL=(root) NOPASSWD: /bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
$RUN_USER ALL=(root) NOPASSWD: /usr/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
$RUN_USER ALL=(root) NOPASSWD: /usr/bin/systemctl restart bunny-door
$RUN_USER ALL=(root) NOPASSWD: /bin/systemctl restart bunny-door
EOF
sudo chmod 0440 /etc/sudoers.d/bunny-door
# ลบไฟล์เก่าถ้ามี (เพราะเปลี่ยนชื่อจาก bunny-door-wifi → bunny-door)
sudo rm -f /etc/sudoers.d/bunny-door-wifi
ok "โฟลเดอร์ + sudoers พร้อม"

# ── Step 8: systemd service ─────────────────────────────────
step "สร้าง systemd service"
SERVICE_FILE=/etc/systemd/system/bunny-door.service
sudo tee "$SERVICE_FILE" > /dev/null <<EOF
[Unit]
Description=Bunny Door Face Recognition Server
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=$RUN_USER
WorkingDirectory=$SCRIPT_DIR
Environment=PATH=$VENV_DIR/bin:/usr/bin:/bin
Environment=PYTHONUNBUFFERED=1
ExecStart=$VENV_DIR/bin/python $SCRIPT_DIR/face_server.py
Restart=always
RestartSec=10
MemoryHigh=1500M
MemoryMax=2000M
StandardOutput=journal
StandardError=journal
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
EOF

# Limit journal size to save SD card
sudo mkdir -p /etc/systemd/journald.conf.d
sudo tee /etc/systemd/journald.conf.d/bunny-door.conf > /dev/null <<EOF
[Journal]
SystemMaxUse=100M
EOF
sudo systemctl restart systemd-journald
sudo systemctl daemon-reload
sudo systemctl enable bunny-door >> "$LOG_FILE" 2>&1
sudo systemctl start bunny-door
sleep 3
if sudo systemctl is-active --quiet bunny-door; then
    ok "Service bunny-door กำลังรัน"
else
    err "Service ไม่ start — ดู: sudo journalctl -u bunny-door -n 50"
fi

# ── Summary ─────────────────────────────────────────────────
END_TS=$(date +%s)
ELAPSED=$(( (END_TS - START_TS) / 60 ))
PI_IP=$(hostname -I | awk '{print $1}')

echo -e "\n${GREEN}"
echo "╔══════════════════════════════════════════════════════════╗"
echo "║              ✅ ติดตั้งเสร็จสมบูรณ์ (${ELAPSED} นาที)              ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"
cat <<EOF
${YELLOW}── Zero-Config ขั้นต่อไป (แค่กดอนุมัติในเว็บ) ──${NC}
1. เปิดเว็บ admin บน PC:
     http://<PC_IP>/bunny-door/network.php
     ↓
     ภายใน ~30 วินาที จะเห็น Pi เครื่องนี้ขึ้นมาเป็น PENDING (สีเหลือง)

2. กดปุ่ม "อนุมัติ" → Pi จะ:
     - ดึง DB credentials อัตโนมัติจาก /api/pair/credentials.php
     - อัปเดต .env ของตัวเอง
     - เริ่ม face recognition (ไม่ต้อง restart)

3. คัดลอกรูปใบหน้าจาก PC (PowerShell):
     scp E:\\path\\to\\photos\\*.jpg $RUN_USER@$PI_IP:$SCRIPT_DIR/images/

4. ดู log real-time:
     sudo journalctl -u bunny-door -f

5. คำสั่งที่ใช้บ่อย:
     sudo systemctl restart bunny-door
     sudo systemctl status bunny-door

${YELLOW}IP ของ Pi เครื่องนี้: $PI_IP${NC}
${YELLOW}Log เต็ม: $LOG_FILE${NC}
EOF

if ! groups "$RUN_USER" | grep -q "\bvideo\b" && [[ "$RUN_USER" == "$USER" ]]; then
    warn "ต้อง reboot 1 ครั้งเพื่อใช้กลุ่ม video ของกล้อง: sudo reboot"
fi
