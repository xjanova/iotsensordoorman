"""
Bunny Door System - Configuration
ค่าตั้งระบบทั้งหมด
"""

# ============================================================
# Database (MySQL on Laragon)
# ============================================================
DB_HOST = "localhost"
DB_PORT = 3306
DB_USER = "root"
DB_PASSWORD = "Theking222"
DB_NAME = "bunny_door"

# ============================================================
# Camera Settings (USB Cameras on Raspberry Pi)
# ============================================================
CAMERA_OUTSIDE_ID = 0     # กล้อง USB ตัวที่ 1 (ด้านนอก)
CAMERA_INSIDE_ID = 1      # กล้อง USB ตัวที่ 2 (ด้านใน)
CAMERA_WIDTH = 640
CAMERA_HEIGHT = 480
CAMERA_FPS = 30

# ============================================================
# Face Recognition
# ============================================================
IMAGES_PATH = "images/"                # โฟลเดอร์รูปใบหน้า
FACE_CONFIDENCE_THRESHOLD = 0.6        # ค่า tolerance (ยิ่งต่ำ = ยิ่งเข้มงวด)
FRAME_RESIZING = 0.25                  # ลดขนาดภาพเป็น 25% เพื่อเพิ่มความเร็ว
PROCESS_EVERY_X_FRAMES = 5            # ประมวลผลทุก 5 เฟรม
FACE_MODEL = "hog"                     # "hog" (เร็ว, CPU) หรือ "cnn" (แม่นยำ, GPU)

# ============================================================
# ESP32 Communication
# ============================================================
ESP32_IP = "192.168.1.100"
ESP32_PORT = 80
ESP32_UNLOCK_URL = f"http://{ESP32_IP}:{ESP32_PORT}/api/door/unlock"
ESP32_LOCK_URL = f"http://{ESP32_IP}:{ESP32_PORT}/api/door/lock"
ESP32_STATUS_URL = f"http://{ESP32_IP}:{ESP32_PORT}/api/status"

# ============================================================
# Flask API Server
# ============================================================
API_HOST = "0.0.0.0"
API_PORT = 5000

# ============================================================
# PHP Web Server (Laragon)
# ============================================================
WEB_SERVER_URL = "http://localhost/bunny-door"
WEB_API_URL = f"{WEB_SERVER_URL}/api"

# ============================================================
# Snapshots
# ============================================================
SNAPSHOT_DIR = "snapshots/"            # โฟลเดอร์เก็บรูปถ่ายขณะเข้า-ออก
MAX_SNAPSHOT_DAYS = 30                 # เก็บรูปถ่ายไว้กี่วัน

# ============================================================
# Anomaly Detection (ตรวจจับความผิดปกติ)
# ============================================================
TAILGATE_DETECTION = True              # ตรวจจับการแอบเข้าตาม
MAX_PERSONS_PER_ENTRY = 1             # จำนวนคนสูงสุดต่อการเข้า 1 ครั้ง
SENSOR_TIMEOUT_SEC = 10               # timeout สำหรับจับคู่เซ็นเซอร์กับกล้อง
ALERT_ON_UNKNOWN_FACE = True          # แจ้งเตือนเมื่อเจอคนไม่รู้จัก

# ============================================================
# Door Timing
# ============================================================
DOOR_UNLOCK_SECONDS = 7               # ปลดล็อกกี่วินาที
DOOR_WARNING_SECONDS = 5              # เตือนก่อนล็อกกี่วินาที
