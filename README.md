# Bunny Door System 🐰🚪

ระบบอนุญาตการเข้า-ออกห้องสโตร์ด้วยการประมวลผลภาพ
**Authorized Access System for Store Room**

## Features
- Face Recognition (HOG/CNN + 128-D Face Embedding)
- Dual PIR Sensors (inside + outside door)
- Dual USB Cameras (entry + exit recording)
- Tailgating Detection (anomaly alerts)
- ESP32 IoT Controller (WiFi HTTP)
- Web Dashboard (PHP + MySQL on Laragon)
- MJPEG Live Streaming

## Tech Stack
| Component | Technology |
|-----------|-----------|
| Face Recognition | Python + OpenCV + face_recognition |
| IoT Controller | ESP32 + Arduino |
| Web Dashboard | PHP 8.3 + MySQL 8.4 + Tailwind CSS |
| Web Server | Laragon (Apache) |
| Communication | HTTP REST API |

## Quick Start

```bash
# 1. Import database
mysql -u root -p < database/schema.sql

# 2. Copy web to Laragon
cp -r web/ C:/laragon/www/bunny-door/

# 3. Install Python deps
cd python && pip install -r requirements.txt

# 4. Upload ESP32 firmware
# Open esp32/door_controller/door_controller.ino in Arduino IDE

# 5. Run Face Server
python python/face_server.py
```

## Documentation
Open `docs/index.html` for full documentation with wiring diagrams.

## Project
สาขาเทคโนโลยีไฟฟ้าอุตสาหกรรม (ต่อเนื่อง)
คณะเทคโนโลยีอุตสาหกรรม
มหาวิทยาลัยราชภัฏวไลยอลงกรณ์ ในพระบรมราชูปถัมภ์
