#!/usr/bin/env bash
# ============================================================
# Bunny Door - สร้าง systemd service (สำหรับเครื่องที่ install-pi.sh ทำไม่ครบ)
# ============================================================
# Usage:
#   cd ~/bunny-door/src && git pull && bash python/fix-service.sh
# ============================================================

set -e

PROJECT_DIR="$HOME/bunny-door/src"
VENV_DIR="$HOME/bunny-door/venv"
RUN_USER="${USER:-pi}"

if [ ! -x "$VENV_DIR/bin/python" ]; then
    echo "✘ ไม่เจอ venv ที่ $VENV_DIR — ต้องรัน install-pi.sh ใหม่ก่อน"
    exit 1
fi

if [ ! -f "$PROJECT_DIR/python/face_server.py" ]; then
    echo "✘ ไม่เจอ face_server.py ที่ $PROJECT_DIR/python/"
    exit 1
fi

echo "── สร้าง /etc/systemd/system/bunny-door.service ──"
sudo tee /etc/systemd/system/bunny-door.service > /dev/null <<EOF
[Unit]
Description=Bunny Door Face Recognition Server
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=$RUN_USER
WorkingDirectory=$PROJECT_DIR/python
Environment=PATH=$VENV_DIR/bin:/usr/bin:/bin
Environment=PYTHONUNBUFFERED=1
ExecStart=$VENV_DIR/bin/python $PROJECT_DIR/python/face_server.py
# Restart=always — ค้างก็ restart ไม่ว่า exit code อะไร
Restart=always
RestartSec=10
# Memory limits — Pi 4GB ใช้ 1.5GB pad, เกิน 2GB จะถูก kill อัตโนมัติ
MemoryHigh=1500M
MemoryMax=2000M
StandardOutput=journal
StandardError=journal
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
EOF

echo "── จำกัด journal log 100M ──"
sudo mkdir -p /etc/systemd/journald.conf.d
sudo tee /etc/systemd/journald.conf.d/bunny-door.conf > /dev/null <<EOF
[Journal]
SystemMaxUse=100M
EOF
sudo systemctl restart systemd-journald

echo "── daemon-reload + enable + start ──"
sudo systemctl daemon-reload
sudo systemctl enable bunny-door
sudo systemctl start bunny-door

echo ""
echo "รอ 5 วินาที..."
sleep 5

echo ""
echo "═══════════════════════════════════════"
echo "  สถานะ Service"
echo "═══════════════════════════════════════"
sudo systemctl status bunny-door --no-pager | head -12

echo ""
echo "═══════════════════════════════════════"
echo "  Port + UDP"
echo "═══════════════════════════════════════"
ss -tlnp 2>/dev/null | grep ":5000" || echo "✘ ไม่มี process ฟัง :5000"
ss -ulnp 2>/dev/null | grep ":47474" || echo "✘ ไม่มี UDP :47474"

echo ""
echo "═══════════════════════════════════════"
echo "  API /api/status"
echo "═══════════════════════════════════════"
curl -s -m 5 http://localhost:5000/api/status | head -c 500
echo ""

echo ""
echo "═══════════════════════════════════════"
echo "  log ล่าสุด 20 บรรทัด"
echo "═══════════════════════════════════════"
sudo journalctl -u bunny-door -n 20 --no-pager
