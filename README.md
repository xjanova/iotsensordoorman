<p align="center">
  <img src="https://img.shields.io/badge/ESP32-IoT-green?style=for-the-badge&logo=espressif&logoColor=white" alt="ESP32">
  <img src="https://img.shields.io/badge/Raspberry_Pi_4-Face_AI-red?style=for-the-badge&logo=raspberrypi&logoColor=white" alt="Raspberry Pi">
  <img src="https://img.shields.io/badge/PHP_8.3-Web_Dashboard-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Python-Face_Recognition-3776AB?style=for-the-badge&logo=python&logoColor=white" alt="Python">
  <img src="https://img.shields.io/badge/MySQL_8.4-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
</p>

<h1 align="center">🐰 Bunny Door System v2.0</h1>

<p align="center">
  <strong>ระบบอนุญาตการเข้า–ออกห้องสโตร์ด้วยการประมวลผลภาพ</strong><br>
  Authorized Access Control System for Store Room using Face Recognition & IoT
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#architecture">Architecture</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#hardware">Hardware</a> •
  <a href="#installation">Installation</a> •
  <a href="#screenshots">Screenshots</a>
</p>

---

## Features

### 🔐 Access Control
- **Face Recognition** — HOG model + 128-D Face Embedding ตรวจจับและจดจำใบหน้าพนักงาน
- **Dual PIR Sensors** — เซ็นเซอร์ตรวจจับความเคลื่อนไหว 2 ตัว (นอก+ใน) trigger กล้องอัตโนมัติ
- **Dual USB Cameras** — กล้อง 2 ตัว ถ่ายภาพใบหน้าด้านนอกและด้านในประตู
- **Electromagnetic Lock 12V** — กลอนแม่เหล็กไฟฟ้าปลดล็อกอัตโนมัติเมื่อยืนยันตัวตน
- **Emergency Button** — ปุ่มปลดล็อกฉุกเฉิน (ด้านในห้อง)

### 🛡️ Security & Alerts
- **Tailgating Detection** — ตรวจจับการเดินตามเข้าห้องพร้อมกัน
- **Unknown Face Alert** — แจ้งเตือนเมื่อพบใบหน้าที่ไม่ได้ลงทะเบียน
- **Forced Entry Detection** — ตรวจจับการเปิดประตูโดยไม่ผ่านระบบ
- **Multi-Person Alert** — แจ้งเตือนเมื่อมีหลายคนเข้าพร้อมกัน

### 📊 Web Admin Dashboard
- **Admin Authentication** — ระบบล็อกอินแอดมิน (ตั้งรหัสครั้งแรก + bcrypt)
- **Dashboard สถิติ** — ภาพรวมเข้า-ออกรายวัน, สถานะอุปกรณ์ Real-time
- **จัดการพนักงาน** — CRUD ข้อมูลพนักงาน, สิทธิ์เข้า-ออก, รูปใบหน้า
- **ประวัติเข้า-ออก** — Log ทุกการเข้า-ออกพร้อม timestamp และรูปถ่าย
- **กล้องวงจรปิด** — MJPEG Live Streaming จากกล้องทั้ง 2 ตัว
- **การแจ้งเตือน** — ดูและจัดการ Alert ความผิดปกติ
- **ตั้งค่าระบบ** — ปรับค่า Door Lock, Confidence, PIR, WiFi, ESP32 จากหน้าเว็บ
- **สร้างโค้ด Arduino** — Generate โค้ด .ino จากค่าตั้งค่าในระบบ พร้อม Copy
- **คู่มือระบบ** — เอกสารครบ 4 แท็บ: ภาพรวม, การต่อวงจร (พร้อมรูปอุปกรณ์), โค้ด Arduino, ติดตั้ง Raspberry Pi

---

## Architecture

```
┌──────────────┐     WiFi HTTP      ┌──────────────────┐
│   ESP32      │ ◄────────────────► │  Raspberry Pi 4  │
│              │  motion/heartbeat  │                  │
│ • PIR x2     │  unlock command    │ • Face Server    │
│ • Relay      │                    │ • USB Camera x2  │
│ • Buzzer     │                    │ • MariaDB        │
│ • LED x2     │                    │ • Python Flask   │
│ • Emergency  │                    │                  │
└──────┬───────┘                    └────────┬─────────┘
       │                                     │
       │  12V DC                             │  HTTP :5000
       ▼                                     ▼
┌──────────────┐                    ┌──────────────────┐
│ กลอนแม่เหล็ก │                    │  Web Dashboard   │
│ Solenoid Lock│                    │  PHP + Tailwind  │
└──────────────┘                    └──────────────────┘
```

### ขั้นตอนการทำงาน

```
คนเดินมา → PIR ตรวจจับ → กล้องถ่ายรูป → จดจำใบหน้า → ✅ ปลดล็อกประตู
                                                      → ❌ แจ้งเตือน Alert
```

---

## Tech Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **IoT Controller** | ESP32-WROOM-32 + Arduino | ESP-IDF |
| **Face Recognition** | Python + OpenCV + face_recognition (HOG) | Python 3.11 |
| **Web Dashboard** | PHP + Tailwind CSS (Dark Theme) | PHP 8.3 |
| **Database** | MySQL / MariaDB | MySQL 8.4 |
| **Web Server** | Apache (Laragon) | httpd 2.4 |
| **API Server** | Python Flask + Flask-CORS | Flask 3.x |
| **Frontend** | Tailwind CSS CDN + Font Awesome | Tailwind 3.x |

---

## Hardware

### อุปกรณ์ที่ต้องใช้

| # | อุปกรณ์ | รุ่น | จำนวน | ราคาประมาณ |
|---|---------|------|--------|-----------|
| 1 | ESP32 Dev Board | ESP32-WROOM-32 (30 pin) | 1 | 120-250 ฿ |
| 2 | PIR Motion Sensor | HC-SR501 | 2 | 25-50 ฿/ตัว |
| 3 | Relay Module | 5V 1-Channel (Optocoupler) | 1 | 20-45 ฿ |
| 4 | Electromagnetic Lock | Solenoid Lock 12V DC | 1 | 80-200 ฿ |
| 5 | USB Webcam | 720p+ (UVC compatible) | 2 | 150-500 ฿/ตัว |
| 6 | Raspberry Pi | Model 4B (4GB RAM+) | 1 | 1,800-3,500 ฿ |
| 7 | LED 5mm | เขียว + แดง | 2 | ~2 ฿/ตัว |
| 8 | Active Buzzer | 5V | 1 | ~10 ฿ |
| 9 | Push Button | Momentary | 1 | ~5 ฿ |
| 10 | Resistor | 220Ω | 2 | ~1 ฿/ตัว |
| 11 | Power Supply | 12V 2A (กลอน) + 5V 3A USB-C (Pi) | 2 | 100-300 ฿ |

### ESP32 GPIO Pin Mapping

| GPIO | อุปกรณ์ | ทิศทาง | หมายเหตุ |
|------|---------|--------|---------|
| GPIO 27 | PIR Sensor (นอก) | INPUT | ตรวจจับคนด้านนอกประตู |
| GPIO 26 | PIR Sensor (ใน) | INPUT | ตรวจจับคนด้านในห้อง |
| GPIO 25 | Relay Module | OUTPUT | HIGH = ปลดล็อก |
| GPIO 33 | Buzzer | OUTPUT | เสียงแจ้งเตือน |
| GPIO 32 | LED เขียว | OUTPUT | อนุญาต (ผ่าน R 220Ω) |
| GPIO 14 | LED แดง | OUTPUT | ปฏิเสธ (ผ่าน R 220Ω) |
| GPIO 13 | Emergency Button | INPUT_PULLUP | กดค้าง = ปลดล็อกฉุกเฉิน |

---

## Project Structure

```
iotsensordoorman/
├── database/
│   └── schema.sql            # SQL schema + default settings
├── esp32/
│   └── door_controller/
│       └── door_controller.ino   # ESP32 Arduino firmware
├── python/
│   ├── face_server.py        # Flask API + Face Recognition
│   ├── .env.example          # Environment config template
│   └── images/               # Employee face images
├── web/
│   ├── api/                  # REST API endpoints
│   │   ├── access_logs.php
│   │   ├── alerts.php
│   │   ├── employees.php
│   │   ├── settings.php
│   │   └── stats.php
│   ├── includes/
│   │   ├── auth.php          # Authentication middleware
│   │   ├── header.php        # Sidebar + navigation
│   │   └── footer.php        # Toast + utilities
│   ├── config.php            # DB connection + helpers
│   ├── setup.php             # First-time admin setup
│   ├── login.php             # Login page
│   ├── logout.php            # Logout handler
│   ├── index.php             # Dashboard
│   ├── cameras.php           # Live MJPEG streams
│   ├── employees.php         # Employee CRUD
│   ├── logs.php              # Access history
│   ├── alerts.php            # Alert management
│   ├── settings.php          # System settings + Arduino code gen
│   └── guide.php             # Documentation hub (4 tabs)
└── README.md
```

---

## Installation

### 1. Database

```bash
mysql -u root -p < database/schema.sql
```

### 2. Web Dashboard (Laragon / Apache)

```bash
# Copy web files to Apache document root
cp -r web/ C:/laragon/www/bunny-door/

# Create .env file
cp web/.env.example web/.env
# Edit: DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME, FACE_SERVER_URL
```

เปิด `http://localhost/bunny-door/` → ระบบจะ redirect ไปหน้า **Setup** เพื่อตั้งรหัสแอดมินครั้งแรก

### 3. ESP32 Firmware

1. เปิด `esp32/door_controller/door_controller.ino` ใน **Arduino IDE**
2. ติดตั้ง Board: **ESP32 Dev Module** (via Board Manager)
3. ติดตั้ง Library: **ArduinoJson** by Benoit Blanchon
4. แก้ไข WiFi SSID, Password, Server URL ในโค้ด (หรือใช้ **ตั้งค่าระบบ > สร้างโค้ด Arduino** จากหน้าเว็บ)
5. เลือก Port > Upload

### 4. Raspberry Pi (Face Recognition Server)

```bash
# Install dependencies
sudo apt install -y python3-pip python3-venv cmake build-essential \
  libopenblas-dev liblapack-dev libatlas-base-dev libhdf5-dev \
  libjpeg-dev libpng-dev mariadb-server

# Create virtual environment
python3 -m venv bunny-env && source bunny-env/bin/activate

# Install Python packages (dlib takes ~30-60 min on Pi)
pip install dlib face_recognition flask flask-cors opencv-python-headless \
  numpy mysql-connector-python requests Pillow

# Run server
cd python && python face_server.py
```

ดูคู่มือเต็มได้ที่ **หน้าเว็บ > คู่มือระบบ > แท็บ "ติดตั้ง Raspberry Pi"**

---

## Security Features

- 🔒 **Admin Authentication** — bcrypt password hashing, session-based login
- 🛡️ **First-time Setup** — ตั้งรหัสแอดมินครั้งแรกอัตโนมัติ
- ⏱️ **Rate Limiting** — ล็อก 15 นาทีหลังล็อกอินผิด 5 ครั้ง
- 🔄 **Session Security** — httponly, samesite=Strict, session_regenerate_id
- 🧹 **XSS Protection** — htmlspecialchars() + esc() ทุก output
- 🔑 **API Auth** — POST/DELETE ต้อง session auth (GET เปิดสำหรับ ESP32/Pi)

---

## Screenshots

> หน้า Web Dashboard ใช้ **Dark Theme + Glassmorphism** design
>
> เข้าดูได้ที่ `http://localhost/bunny-door/` หลังติดตั้ง

| หน้า | คำอธิบาย |
|------|---------|
| Setup | ตั้งรหัสแอดมินครั้งแรก |
| Login | เข้าสู่ระบบ |
| Dashboard | ภาพรวมสถิติ + สถานะอุปกรณ์ |
| Employees | จัดการพนักงาน CRUD |
| Cameras | กล้องสด MJPEG |
| Logs | ประวัติเข้า-ออก |
| Alerts | การแจ้งเตือนความผิดปกติ |
| Settings | ตั้งค่าระบบ + สร้างโค้ด Arduino |
| Guide | คู่มือ 4 แท็บ (ภาพรวม, วงจร, Arduino, Raspberry Pi) |

---

## Development

พัฒนาโดย **XMAN Studio**

### Project Info

| | |
|---|---|
| **โครงงาน** | ระบบอนุญาตการเข้า–ออกห้องสโตร์ด้วยการประมวลผลภาพ |
| **สาขา** | เทคโนโลยีไฟฟ้าอุตสาหกรรม (ต่อเนื่อง) |
| **คณะ** | เทคโนโลยีอุตสาหกรรม |
| **มหาวิทยาลัย** | มหาวิทยาลัยราชภัฏวไลยอลงกรณ์ ในพระบรมราชูปถัมภ์ |
| **พัฒนาโดย** | **XMAN Studio** |

---

<p align="center">
  <strong>Bunny Door System v2.0</strong> — IoT Face Recognition Access Control<br>
  Developed by <strong>XMAN Studio</strong> 🐰
</p>
