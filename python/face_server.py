"""
Bunny Door System - Face Recognition + API Server (Lite)
=========================================================
ปรับปรุงสำหรับ Raspberry Pi 2GB RAM:
1. ใช้ Snapshot mode แทน MJPEG streaming (ประหยัด connection + CPU)
2. ลดความถี่อ่านเฟรม (~5 FPS) และประมวลผลใบหน้าทุก 15 เฟรม
3. ลดความละเอียดกล้อง 320x240
4. ไม่ใช้ BackgroundSubtractor (ประหยัด RAM)
5. Flask API สำหรับสื่อสารกับ PHP Dashboard
"""

import cv2
import numpy as np
import os
import sys
import time
import json
import signal
import threading
import requests
import mysql.connector
from datetime import datetime
from collections import Counter
from flask import Flask, Response, jsonify, request
from flask_cors import CORS
import face_recognition
from simple_facerec import SimpleFaceRec
import logging
import config

# ============================================================
# Initialize
# ============================================================
app = Flask(__name__)
CORS(app)

# ============================================================
# File-based Logging (สำหรับดูผ่านเว็บ)
# ============================================================
LOG_FILE = os.path.join(os.path.dirname(__file__), 'face_server.log')
_log_handler = logging.FileHandler(LOG_FILE, encoding='utf-8')
_log_handler.setFormatter(logging.Formatter('%(asctime)s [%(levelname)s] %(message)s', datefmt='%Y-%m-%d %H:%M:%S'))
_log_handler.setLevel(logging.DEBUG)

# หัก stdout ของ print ไปเก็บใน log file ด้วย
class _TeeWriter:
    def __init__(self, original, log_file):
        self.original = original
        self.log_file = log_file
    def write(self, msg):
        self.original.write(msg)
        if msg.strip():
            try:
                with open(self.log_file, 'a', encoding='utf-8') as f:
                    f.write(f"{datetime.now().strftime('%Y-%m-%d %H:%M:%S')} [INFO] {msg.strip()}\n")
            except Exception:
                pass
    def flush(self):
        self.original.flush()

sys.stdout = _TeeWriter(sys.__stdout__, LOG_FILE)
sys.stderr = _TeeWriter(sys.__stderr__, LOG_FILE)

# Face Recognition
sfr = SimpleFaceRec(
    model=config.FACE_MODEL,
    tolerance=config.FACE_CONFIDENCE_THRESHOLD,
    frame_resizing=config.FRAME_RESIZING
)
sfr.load_encoding_images(config.IMAGES_PATH)

# State (ไม่ใช้ BackgroundSubtractor เพื่อประหยัด RAM)
system_state = {
    "camera_outside": {"jpeg": None, "faces": [], "motion": False, "lock": threading.Lock()},
    "camera_inside": {"jpeg": None, "faces": [], "motion": False, "lock": threading.Lock()},
    "door_status": "locked",
    "last_motion_outside": 0,
    "last_motion_inside": 0,
    "esp32_online": False,
    "people_inside": 0,
    "running": True,
}

# Snapshot directory
os.makedirs(config.SNAPSHOT_DIR, exist_ok=True)
os.makedirs(config.IMAGES_PATH, exist_ok=True)


# ============================================================
# Database Helper
# ============================================================
def get_db():
    return mysql.connector.connect(
        host=config.DB_HOST,
        port=config.DB_PORT,
        user=config.DB_USER,
        password=config.DB_PASSWORD,
        database=config.DB_NAME,
        charset="utf8mb4"
    )


def log_access(employee_id, direction, method, confidence, camera_id, sensor_id, snapshot, authorized):
    db = None
    try:
        db = get_db()
        cursor = db.cursor()
        cursor.execute("""
            INSERT INTO access_logs
            (employee_id, direction, method, confidence, camera_id, sensor_triggered, snapshot_path, is_authorized)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """, (employee_id, direction, method, confidence, camera_id, sensor_id, snapshot, authorized))
        db.commit()
        cursor.close()
    except Exception as e:
        print(f"[DB Error] {e}")
    finally:
        if db:
            db.close()


def log_anomaly(alert_type, severity, description, camera_id, snapshot):
    db = None
    try:
        db = get_db()
        cursor = db.cursor()
        cursor.execute("""
            INSERT INTO anomaly_alerts (alert_type, severity, description, camera_id, snapshot_path)
            VALUES (%s, %s, %s, %s, %s)
        """, (alert_type, severity, description, camera_id, snapshot))
        db.commit()
        cursor.close()
    except Exception as e:
        print(f"[DB Error] {e}")
    finally:
        if db:
            db.close()


def get_employee_by_name(name):
    db = None
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute("""
            SELECT * FROM employees
            WHERE face_image LIKE %s AND is_authorized = 1
        """, (f"{name}%",))
        result = cursor.fetchone()
        cursor.close()
        return result
    except Exception as e:
        print(f"[DB Error] {e}")
        return None
    finally:
        if db:
            db.close()


def update_system_status(component, status, ip=None):
    db = None
    try:
        db = get_db()
        cursor = db.cursor()
        cursor.execute("""
            UPDATE system_status
            SET status = %s, last_heartbeat = NOW(), ip_address = %s
            WHERE component = %s
        """, (status, ip, component))
        db.commit()
        cursor.close()
    except Exception as e:
        print(f"[DB Error] {e}")
    finally:
        if db:
            db.close()


# ============================================================
# Camera Processing Thread
# ============================================================
_camera_captures = {}  # เก็บ VideoCapture objects สำหรับ cleanup

def camera_thread(camera_id, cam_name):
    """Thread สำหรับประมวลผลกล้องแต่ละตัว (Lite mode สำหรับ Pi 2GB)"""
    print(f"[Camera] Starting {cam_name} (ID: {camera_id})")

    cap = cv2.VideoCapture(camera_id, cv2.CAP_V4L2)
    if not cap.isOpened():
        print(f"[Camera] Failed to open {cam_name}")
        update_system_status(cam_name, "OFFLINE")
        return

    _camera_captures[cam_name] = cap
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, config.CAMERA_WIDTH)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, config.CAMERA_HEIGHT)
    cap.set(cv2.CAP_PROP_FPS, config.CAMERA_FPS)
    update_system_status(cam_name, "ONLINE")

    frame_count = 0
    last_faces = []
    last_names = []
    last_confidences = []

    while system_state["running"]:
        ret, frame = cap.read()
        if not ret:
            time.sleep(0.5)
            continue

        frame_count += 1
        cam_state = system_state[cam_name]

        # Face recognition ทุก X เฟรม
        if frame_count % config.PROCESS_EVERY_X_FRAMES == 0:
            faces, names, confidences = sfr.detect_known_faces(frame)
            last_faces = faces
            last_names = names
            last_confidences = confidences
            cam_state["motion"] = len(names) > 0

            if names:
                process_detected_faces(
                    frame, faces, names, confidences,
                    camera_id=1 if cam_name == "camera_outside" else 2,
                    cam_name=cam_name
                )

        # วาดกรอบใบหน้า (เฉพาะเมื่อมี)
        display_frame = frame
        if last_faces:
            display_frame = frame.copy()
            for (top, right, bottom, left), name, conf in zip(last_faces, last_names, last_confidences):
                color = (0, 255, 0) if name != "Unknown" else (0, 0, 255)
                cv2.rectangle(display_frame, (left, top), (right, bottom), color, 2)
                label = f"{name} ({conf}%)" if conf > 0 else "Unknown"
                cv2.putText(display_frame, label, (left, top - 10),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 1)

        # Encode เป็น JPEG เก็บไว้ (ไม่ต้อง stream ตลอด)
        _, jpeg_buf = cv2.imencode('.jpg', display_frame, [cv2.IMWRITE_JPEG_QUALITY, 65])
        with cam_state["lock"]:
            cam_state["jpeg"] = jpeg_buf.tobytes()
            cam_state["faces"] = list(zip(last_names, last_confidences))

        # หน่วงเวลา ~5 FPS เพื่อประหยัด CPU
        time.sleep(0.2)

    cap.release()
    update_system_status(cam_name, "OFFLINE")
    print(f"[Camera] {cam_name} stopped")


# ============================================================
# Face Processing Logic
# ============================================================
# Debounce: ป้องกันการ process ซ้ำในเวลาสั้น
_last_action_time = {}  # {"person_name": timestamp}
DEBOUNCE_SECONDS = 10

def process_detected_faces(frame, faces, names, confidences, camera_id, cam_name):
    """ประมวลผลใบหน้าที่ตรวจพบ (มี debounce)"""
    direction = "IN" if cam_name == "camera_outside" else "OUT"
    now = time.time()

    for (top, right, bottom, left), name, conf in zip(faces, names, confidences):
        # Debounce check
        debounce_key = f"{name}_{cam_name}"
        if debounce_key in _last_action_time and (now - _last_action_time[debounce_key]) < DEBOUNCE_SECONDS:
            continue

        if name == "Unknown":
            if config.ALERT_ON_UNKNOWN_FACE:
                _last_action_time[debounce_key] = now
                snapshot = save_snapshot(frame, "unknown", camera_id)
                log_anomaly(
                    "UNKNOWN_FACE", "HIGH",
                    f"พบบุคคลไม่รู้จักที่ {cam_name}",
                    camera_id, snapshot
                )
                log_access(None, direction, "FACE", 0, camera_id, None, snapshot, 0)
            continue

        _last_action_time[debounce_key] = now

        # ค้นหาพนักงานในฐานข้อมูล
        employee = get_employee_by_name(name)
        if employee and employee["is_authorized"]:
            snapshot = save_snapshot(frame, name, camera_id)
            log_access(employee["id"], direction, "FACE", conf, camera_id, None, snapshot, 1)

            # สั่งเปิดประตู (ถ้าเป็นกล้องด้านนอก = ขาเข้า)
            if direction == "IN":
                unlock_door()

    # Tailgating detection
    if len(names) > config.MAX_PERSONS_PER_ENTRY:
        known_count = sum(1 for n in names if n != "Unknown")
        unknown_count = len(names) - known_count
        if unknown_count > 0:
            snapshot = save_snapshot(frame, "tailgating", camera_id)
            log_anomaly(
                "TAILGATING", "CRITICAL",
                f"ตรวจพบ {len(names)} คนพร้อมกัน ({unknown_count} ไม่รู้จัก) ที่ {cam_name}",
                camera_id, snapshot
            )


def save_snapshot(frame, label, camera_id):
    """บันทึกภาพถ่าย"""
    # Sanitize label to prevent path traversal
    label = label.replace("/", "").replace("\\", "").replace("..", "")
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"cam{camera_id}_{label}_{timestamp}.jpg"
    filepath = os.path.join(config.SNAPSHOT_DIR, filename)
    cv2.imwrite(filepath, frame)
    return filename


def unlock_door():
    """สั่ง ESP32 ปลดล็อกประตู"""
    try:
        resp = requests.post(config.ESP32_UNLOCK_URL, timeout=3)
        if resp.status_code == 200:
            system_state["door_status"] = "unlocked"
            print("[Door] Unlocked via ESP32")
        return True
    except Exception as e:
        print(f"[ESP32 Error] {e}")
        return False


# ============================================================
# Snapshot API (แทน MJPEG stream เพื่อประหยัด resource)
# ============================================================


# ============================================================
# Flask API Routes
# ============================================================

@app.route('/')
def index():
    return jsonify({"system": "Bunny Door System", "status": "running"})


@app.route('/api/snapshot/outside')
def snapshot_outside():
    """ส่ง JPEG snapshot ล่าสุดจากกล้องด้านนอก"""
    cam_state = system_state["camera_outside"]
    with cam_state["lock"]:
        jpeg = cam_state["jpeg"]
    if jpeg:
        return Response(jpeg, mimetype='image/jpeg',
                        headers={'Cache-Control': 'no-cache, no-store'})
    return Response(status=204)


@app.route('/api/snapshot/inside')
def snapshot_inside():
    """ส่ง JPEG snapshot ล่าสุดจากกล้องด้านใน"""
    cam_state = system_state["camera_inside"]
    with cam_state["lock"]:
        jpeg = cam_state["jpeg"]
    if jpeg:
        return Response(jpeg, mimetype='image/jpeg',
                        headers={'Cache-Control': 'no-cache, no-store'})
    return Response(status=204)


@app.route('/api/status')
def api_status():
    return jsonify({
        "door": system_state["door_status"],
        "camera_outside": {
            "motion": system_state["camera_outside"]["motion"],
            "faces": system_state["camera_outside"]["faces"]
        },
        "camera_inside": {
            "motion": system_state["camera_inside"]["motion"],
            "faces": system_state["camera_inside"]["faces"]
        },
        "people_inside": system_state["people_inside"],
        "face_database_count": sfr.get_face_count(),
        "timestamp": datetime.now().isoformat()
    })


@app.route('/api/door/unlock', methods=['POST'])
def api_door_unlock():
    success = unlock_door()
    return jsonify({"success": success, "status": "unlocked" if success else "error"})


@app.route('/api/door/lock', methods=['POST'])
def api_door_lock():
    try:
        resp = requests.post(config.ESP32_LOCK_URL, timeout=3)
        system_state["door_status"] = "locked"
        return jsonify({"success": True, "status": "locked"})
    except Exception as e:
        print(f"[ESP32 Error] {e}")
        return jsonify({"success": False, "error": "Failed to communicate with ESP32"}), 500


@app.route('/api/motion', methods=['POST'])
def api_motion():
    """รับข้อมูล motion จาก ESP32"""
    data = request.json
    if not data:
        return jsonify({"error": "No JSON body"}), 400
    sensor = data.get("sensor", "unknown")
    now = time.time()

    if sensor == "outside":
        system_state["last_motion_outside"] = now
    elif sensor == "inside":
        system_state["last_motion_inside"] = now

    return jsonify({"received": True, "sensor": sensor})


@app.route('/api/heartbeat', methods=['POST'])
def api_heartbeat():
    """รับ heartbeat จาก ESP32"""
    data = request.json
    if not data:
        return jsonify({"error": "No JSON body"}), 400
    system_state["esp32_online"] = True
    system_state["door_status"] = data.get("door", "unknown")
    update_system_status("esp32", "ONLINE", config.ESP32_IP)
    return jsonify({"received": True})


@app.route('/api/emergency', methods=['POST'])
def api_emergency():
    """รับสัญญาณ emergency จาก ESP32"""
    log_anomaly("FORCED_ENTRY", "CRITICAL", "Emergency button pressed", None, None)
    system_state["door_status"] = "unlocked"
    return jsonify({"received": True})


@app.route('/api/employees', methods=['GET'])
def api_employees():
    db = None
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute("SELECT id, emp_code, first_name, last_name, department, position, face_image, is_authorized FROM employees ORDER BY emp_code")
        employees = cursor.fetchall()
        cursor.close()
        return jsonify(employees)
    except Exception as e:
        print(f"[DB Error] {e}")
        return jsonify({"error": "Database error"}), 500
    finally:
        if db:
            db.close()


@app.route('/api/logs/recent', methods=['GET'])
def api_recent_logs():
    limit = min(request.args.get('limit', 50, type=int), 500)
    db = None
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute("""
            SELECT al.*, e.first_name, e.last_name, e.emp_code
            FROM access_logs al
            LEFT JOIN employees e ON al.employee_id = e.id
            ORDER BY al.created_at DESC
            LIMIT %s
        """, (limit,))
        logs = cursor.fetchall()
        cursor.close()
        # Convert datetime to string
        for log in logs:
            if log.get("created_at"):
                log["created_at"] = log["created_at"].isoformat()
        return jsonify(logs)
    except Exception as e:
        print(f"[DB Error] {e}")
        return jsonify({"error": "Database error"}), 500
    finally:
        if db:
            db.close()


@app.route('/api/alerts/recent', methods=['GET'])
def api_recent_alerts():
    limit = min(request.args.get('limit', 20, type=int), 200)
    db = None
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute("""
            SELECT * FROM anomaly_alerts ORDER BY created_at DESC LIMIT %s
        """, (limit,))
        alerts = cursor.fetchall()
        cursor.close()
        for alert in alerts:
            if alert.get("created_at"):
                alert["created_at"] = alert["created_at"].isoformat()
            if alert.get("resolved_at"):
                alert["resolved_at"] = alert["resolved_at"].isoformat()
        return jsonify(alerts)
    except Exception as e:
        print(f"[DB Error] {e}")
        return jsonify({"error": "Database error"}), 500
    finally:
        if db:
            db.close()


@app.route('/api/stats', methods=['GET'])
def api_stats():
    db = None
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)

        # จำนวนพนักงานทั้งหมด
        cursor.execute("SELECT COUNT(*) as total FROM employees")
        total_employees = cursor.fetchone()["total"]

        # เข้าวันนี้
        cursor.execute("""
            SELECT COUNT(DISTINCT employee_id) as count
            FROM access_logs
            WHERE direction='IN' AND DATE(created_at) = CURDATE() AND is_authorized = 1
        """)
        today_in = cursor.fetchone()["count"]

        # ออกวันนี้
        cursor.execute("""
            SELECT COUNT(DISTINCT employee_id) as count
            FROM access_logs
            WHERE direction='OUT' AND DATE(created_at) = CURDATE() AND is_authorized = 1
        """)
        today_out = cursor.fetchone()["count"]

        # แจ้งเตือนที่ยังไม่แก้ไข
        cursor.execute("SELECT COUNT(*) as count FROM anomaly_alerts WHERE is_resolved = 0")
        unresolved_alerts = cursor.fetchone()["count"]

        # แจ้งเตือนวันนี้
        cursor.execute("SELECT COUNT(*) as count FROM anomaly_alerts WHERE DATE(created_at) = CURDATE()")
        today_alerts = cursor.fetchone()["count"]

        cursor.close()

        return jsonify({
            "total_employees": total_employees,
            "today_in": today_in,
            "today_out": today_out,
            "currently_inside": max(0, today_in - today_out),
            "unresolved_alerts": unresolved_alerts,
            "today_alerts": today_alerts,
        })
    except Exception as e:
        print(f"[DB Error] {e}")
        return jsonify({"error": "Database error"}), 500
    finally:
        if db:
            db.close()


# ============================================================
# System Health API (CPU, RAM, Temperature ของ Raspberry Pi)
# ============================================================
@app.route('/api/system/health')
def api_system_health():
    """ส่งข้อมูล CPU/RAM/Temperature ของ Raspberry Pi"""
    import psutil

    # CPU usage (%) — non-blocking, ใช้ค่าเฉลี่ยจาก call ก่อนหน้า
    cpu_percent = psutil.cpu_percent(interval=None)

    # RAM usage
    mem = psutil.virtual_memory()
    ram_total_mb = round(mem.total / (1024 * 1024))
    ram_used_mb = round(mem.used / (1024 * 1024))
    ram_percent = mem.percent

    # CPU Temperature (Raspberry Pi)
    cpu_temp = None
    try:
        with open('/sys/class/thermal/thermal_zone0/temp', 'r') as f:
            cpu_temp = round(int(f.read().strip()) / 1000, 1)
    except Exception:
        pass

    # Disk usage
    disk = psutil.disk_usage('/')
    disk_percent = disk.percent

    return jsonify({
        "cpu_percent": cpu_percent,
        "ram_total_mb": ram_total_mb,
        "ram_used_mb": ram_used_mb,
        "ram_percent": ram_percent,
        "cpu_temp": cpu_temp,
        "disk_percent": disk_percent,
        "uptime": round(time.time() - psutil.boot_time()),
    })


# ============================================================
# ESP32 Health API (proxy ดึงข้อมูลจาก ESP32)
# ============================================================
@app.route('/api/esp32/health')
def api_esp32_health():
    """ดึงข้อมูล ESP32 status (door, uptime, rssi, ip)"""
    try:
        resp = requests.get(config.ESP32_STATUS_URL, timeout=3)
        data = resp.json()
        data["online"] = True
        return jsonify(data)
    except Exception:
        return jsonify({"online": False, "error": "ESP32 ไม่ตอบสนอง"})


@app.route('/api/config/esp32', methods=['POST'])
def api_config_esp32():
    """อัพเดท ESP32 IP (เรียกจาก network page)"""
    data = request.json
    if not data or not data.get('ip'):
        return jsonify({"error": "กรุณาระบุ IP"}), 400

    new_ip = data['ip']
    new_port = data.get('port', 80)

    # อัพเดท config runtime
    config.ESP32_IP = new_ip
    config.ESP32_PORT = new_port
    config.ESP32_UNLOCK_URL = f"http://{new_ip}:{new_port}/api/door/unlock"
    config.ESP32_LOCK_URL = f"http://{new_ip}:{new_port}/api/door/lock"
    config.ESP32_STATUS_URL = f"http://{new_ip}:{new_port}/api/status"

    # อัพเดท .env file
    env_path = os.path.join(os.path.dirname(__file__), '.env')
    lines = []
    found = False
    if os.path.exists(env_path):
        with open(env_path, 'r') as f:
            for line in f:
                if line.strip().startswith('ESP32_IP='):
                    lines.append(f'ESP32_IP={new_ip}\n')
                    found = True
                else:
                    lines.append(line)
    if not found:
        lines.append(f'ESP32_IP={new_ip}\n')
    with open(env_path, 'w') as f:
        f.writelines(lines)

    print(f"[Config] ESP32 IP updated to {new_ip}")
    return jsonify({"success": True, "esp32_ip": new_ip})


# ============================================================
# Camera Detection API (สแกนกล้อง USB ที่เสียบอยู่)
# ============================================================
@app.route('/api/cameras/detect')
def api_cameras_detect():
    """สแกนหากล้อง USB ทั้งหมดที่เชื่อมต่อ พร้อมแสดงตัวอย่างภาพ"""
    import subprocess
    cameras = []

    # ใช้ v4l2-ctl ดูรายการอุปกรณ์
    try:
        result = subprocess.run(
            ['v4l2-ctl', '--list-devices'],
            capture_output=True, text=True, timeout=5
        )
        v4l2_output = result.stdout
    except Exception:
        v4l2_output = ""

    # parse ชื่ออุปกรณ์จาก v4l2-ctl output
    device_names = {}
    current_name = None
    for line in v4l2_output.split('\n'):
        line = line.rstrip()
        if line and not line.startswith('\t') and not line.startswith(' '):
            current_name = line.rstrip(':')
        elif line.strip().startswith('/dev/video'):
            dev_id = line.strip().replace('/dev/video', '')
            if dev_id.isdigit():
                device_names[int(dev_id)] = current_name

    # สแกน /dev/video0 ถึง /dev/video9
    for i in range(10):
        dev_path = f'/dev/video{i}'
        if not os.path.exists(dev_path):
            continue

        cap = cv2.VideoCapture(i, cv2.CAP_V4L2)
        if not cap.isOpened():
            continue

        # ลองอ่านเฟรม
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, 320)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 240)
        ret, frame = cap.read()
        cap.release()

        if not ret or frame is None:
            continue

        # encode เป็น base64 thumbnail
        import base64
        _, buf = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 50])
        thumb = base64.b64encode(buf).decode('utf-8')

        # ดูว่ากำลังใช้เป็นกล้องไหนอยู่
        assigned = None
        if i == config.CAMERA_OUTSIDE_ID:
            assigned = 'outside'
        elif i == config.CAMERA_INSIDE_ID:
            assigned = 'inside'

        cameras.append({
            'id': i,
            'device': dev_path,
            'name': device_names.get(i, f'Camera {i}'),
            'thumbnail': thumb,
            'assigned': assigned,
            'can_read': True
        })

    return jsonify({
        'cameras': cameras,
        'current_outside': config.CAMERA_OUTSIDE_ID,
        'current_inside': config.CAMERA_INSIDE_ID
    })


@app.route('/api/cameras/assign', methods=['POST'])
def api_cameras_assign():
    """กำหนดว่ากล้อง ID ไหนเป็นด้านนอก/ด้านใน แล้ว restart camera threads"""
    data = request.json
    if not data:
        return jsonify({"error": "No JSON body"}), 400

    outside_id = int(data.get('outside', -1))
    inside_id = int(data.get('inside', -1))

    # อัพเดท config runtime
    config.CAMERA_OUTSIDE_ID = outside_id
    config.CAMERA_INSIDE_ID = inside_id

    # อัพเดท .env file
    env_path = os.path.join(os.path.dirname(__file__), '.env')
    lines = []
    found_out = False
    found_in = False
    if os.path.exists(env_path):
        with open(env_path, 'r') as f:
            for line in f:
                if line.strip().startswith('CAMERA_OUTSIDE_ID='):
                    lines.append(f'CAMERA_OUTSIDE_ID={outside_id}\n')
                    found_out = True
                elif line.strip().startswith('CAMERA_INSIDE_ID='):
                    lines.append(f'CAMERA_INSIDE_ID={inside_id}\n')
                    found_in = True
                else:
                    lines.append(line)
    if not found_out:
        lines.append(f'CAMERA_OUTSIDE_ID={outside_id}\n')
    if not found_in:
        lines.append(f'CAMERA_INSIDE_ID={inside_id}\n')
    with open(env_path, 'w') as f:
        f.writelines(lines)

    print(f"[Config] Camera assigned: outside={outside_id}, inside={inside_id}")
    return jsonify({
        "success": True,
        "outside": outside_id,
        "inside": inside_id,
        "message": "กรุณา restart face_server เพื่อใช้กล้องใหม่"
    })


# ============================================================
# ============================================================
# Web Terminal API (สั่งคำสั่งบน Pi ผ่านเว็บ)
# ============================================================
# คำสั่งที่อนุญาต (whitelist เพื่อความปลอดภัย)
_ALLOWED_COMMANDS = [
    'ls', 'pwd', 'whoami', 'hostname', 'uptime', 'df', 'free',
    'cat', 'head', 'tail', 'wc', 'date', 'uname',
    'ps', 'top', 'htop', 'vcgencmd', 'ip', 'ifconfig', 'ping',
    'v4l2-ctl', 'lsusb', 'lsblk', 'systemctl',
    'pip', 'pip3', 'python', 'python3',
    'git', 'cd', 'echo', 'grep', 'find', 'which',
    'startbunny', 'pkill', 'kill',
]

@app.route('/api/terminal', methods=['POST'])
def api_terminal():
    """รันคำสั่งบน Pi ผ่านเว็บ (จำกัดคำสั่งที่อนุญาต)"""
    data = request.json
    if not data or not data.get('command'):
        return jsonify({"error": "กรุณาระบุคำสั่ง"}), 400

    cmd = data['command'].strip()
    if not cmd:
        return jsonify({"error": "คำสั่งว่าง"}), 400

    # ตรวจสอบคำสั่งแรก (base command)
    base_cmd = cmd.split()[0].split('/')[-1]  # ดึงเฉพาะชื่อคำสั่ง
    if base_cmd not in _ALLOWED_COMMANDS:
        return jsonify({
            "error": f"คำสั่ง '{base_cmd}' ไม่ได้รับอนุญาต",
            "allowed": _ALLOWED_COMMANDS
        }), 403

    # ห้ามใช้ rm, dd, mkfs, fdisk เด็ดขาด
    dangerous = ['rm ', 'rm\t', 'dd ', 'mkfs', 'fdisk', 'format', 'shutdown', 'reboot', 'halt', '> /dev']
    for d in dangerous:
        if d in cmd:
            return jsonify({"error": f"คำสั่งอันตราย: {d.strip()}"}), 403

    try:
        import subprocess
        result = subprocess.run(
            cmd, shell=True,
            capture_output=True, text=True,
            timeout=30,
            cwd=os.path.expanduser('~/bunny-door')
        )
        return jsonify({
            "success": True,
            "stdout": result.stdout,
            "stderr": result.stderr,
            "returncode": result.returncode
        })
    except subprocess.TimeoutExpired:
        return jsonify({"error": "คำสั่งใช้เวลานานเกิน 30 วินาที"}), 408
    except Exception as e:
        return jsonify({"error": str(e)}), 500


# ============================================================
# Server Log API (ดู/ลบ log ผ่านเว็บ)
# ============================================================
@app.route('/api/logs/server')
def api_logs_server():
    """ดู log ของ face_server"""
    lines = int(request.args.get('lines', 200))
    search = request.args.get('search', '').lower()
    level = request.args.get('level', '')  # INFO, ERROR, WARNING

    if not os.path.exists(LOG_FILE):
        return jsonify({"logs": [], "total_lines": 0, "file_size": 0})

    file_size = os.path.getsize(LOG_FILE)
    with open(LOG_FILE, 'r', encoding='utf-8', errors='replace') as f:
        all_lines = f.readlines()

    total_lines = len(all_lines)

    # กรอง
    filtered = all_lines
    if level:
        filtered = [l for l in filtered if f'[{level}]' in l]
    if search:
        filtered = [l for l in filtered if search in l.lower()]

    # เอาเฉพาะ N บรรทัดท้าย
    result = filtered[-lines:]

    return jsonify({
        "logs": [l.rstrip('\n') for l in result],
        "total_lines": total_lines,
        "filtered_lines": len(filtered),
        "file_size": file_size,
        "file_size_mb": round(file_size / 1024 / 1024, 2)
    })


@app.route('/api/logs/server/clear', methods=['POST'])
def api_logs_clear():
    """ลบ log file"""
    try:
        with open(LOG_FILE, 'w', encoding='utf-8') as f:
            f.write(f"{datetime.now().strftime('%Y-%m-%d %H:%M:%S')} [INFO] Log cleared via web interface\n")
        return jsonify({"success": True, "message": "ลบ log เรียบร้อย"})
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


# ============================================================
# Camera Capture API (ถ่ายภาพจากกล้องตัวเดียวกับที่สแกน)
# ============================================================
@app.route('/api/capture/photo')
def api_capture_photo():
    """ถ่ายภาพคุณภาพสูงจากกล้อง (สำหรับลงทะเบียนใบหน้า)"""
    cam = request.args.get('camera', 'outside')
    cam_key = f"camera_{cam}"

    if cam_key not in system_state:
        return jsonify({"error": f"ไม่พบกล้อง {cam}"}), 404

    # ดึง frame ดิบจากกล้อง (ไม่มีกรอบวาดทับ)
    cam_name = cam_key
    if cam_name not in _camera_captures or not _camera_captures[cam_name].isOpened():
        return jsonify({"error": "กล้องไม่ได้เปิดอยู่"}), 503

    cap = _camera_captures[cam_name]
    ret, frame = cap.read()
    if not ret or frame is None:
        return jsonify({"error": "ไม่สามารถถ่ายภาพได้"}), 503

    # Encode เป็น JPEG คุณภาพสูง (95%)
    _, jpeg_buf = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 95])
    return Response(jpeg_buf.tobytes(), mimetype='image/jpeg',
                    headers={'Cache-Control': 'no-cache, no-store'})


@app.route('/api/capture/save', methods=['POST'])
def api_capture_save():
    """ถ่ายภาพจากกล้องแล้วบันทึกเป็นไฟล์ (สำหรับลงทะเบียน)"""
    cam = request.json.get('camera', 'outside') if request.is_json else request.form.get('camera', 'outside')
    emp_code = request.json.get('emp_code', 'capture') if request.is_json else request.form.get('emp_code', 'capture')

    cam_key = f"camera_{cam}"
    if cam_key not in _camera_captures or not _camera_captures[cam_key].isOpened():
        return jsonify({"error": "กล้องไม่ได้เปิดอยู่"}), 503

    cap = _camera_captures[cam_key]
    ret, frame = cap.read()
    if not ret or frame is None:
        return jsonify({"error": "ไม่สามารถถ่ายภาพได้"}), 503

    # ตรวจจับใบหน้าก่อนบันทึก
    rgb_img = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
    face_locations = face_recognition.face_locations(rgb_img, model="hog")

    if len(face_locations) == 0:
        return jsonify({"error": "ไม่พบใบหน้าในภาพ ลองขยับเข้าใกล้กล้อง", "valid": False}), 400

    if len(face_locations) > 1:
        return jsonify({"error": f"พบ {len(face_locations)} ใบหน้า ควรมีเพียง 1 คนหน้ากล้อง", "valid": False}), 400

    # คำนวณ face ratio
    top, right, bottom, left = face_locations[0]
    face_h = bottom - top
    face_w = right - left
    img_h, img_w = frame.shape[:2]
    face_ratio = round((face_h * face_w) / (img_h * img_w) * 100, 1)

    # ตรวจ encoding ได้ไหม
    face_encodings = face_recognition.face_encodings(rgb_img, face_locations)
    if not face_encodings:
        return jsonify({"error": "ตรวจพบใบหน้าแต่ไม่สามารถวิเคราะห์ได้ ลองปรับแสง", "valid": False}), 400

    # Encode เป็น JPEG แล้วส่ง base64 กลับ (ให้ PHP proxy บันทึกไฟล์เอง)
    import re, base64
    safe_code = re.sub(r'[^A-Za-z0-9\-]', '', emp_code) or 'capture'
    filename = f"{safe_code}.jpg"
    web_filename = f"{safe_code}_{int(time.time())}.jpg"

    _, jpg_buf = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 95])
    img_base64 = base64.b64encode(jpg_buf.tobytes()).decode('utf-8')

    # บันทึกไว้บน Pi — ใช้ emp_code.jpg เพื่อให้ face recognition จำชื่อได้
    save_dir = os.path.join(os.path.dirname(__file__), 'images')
    os.makedirs(save_dir, exist_ok=True)
    filepath = os.path.join(save_dir, filename)
    cv2.imwrite(filepath, frame, [cv2.IMWRITE_JPEG_QUALITY, 95])

    # Reload face encodings อัตโนมัติ
    loaded = sfr.reload_images(config.IMAGES_PATH)
    print(f"[Capture] Saved {filename} → reloaded {loaded} faces")

    return jsonify({
        "success": True,
        "filename": web_filename,
        "face_ratio": face_ratio,
        "face_size": {"width": face_w, "height": face_h},
        "valid": True,
        "quality_notes": ["ใบหน้าเล็ก ลองเข้าใกล้กว่านี้"] if face_ratio < 5 else [],
        "message": "ถ่ายภาพสำเร็จ" + (f" (ใบหน้า {face_ratio}%)" if face_ratio else ""),
        "image_base64": img_base64
    })


# ============================================================
# Face Validation API (for photo upload)
# ============================================================
@app.route('/api/face/validate', methods=['POST'])
def api_face_validate():
    """ตรวจสอบว่ารูปที่อัพโหลดมีใบหน้าหรือไม่ และใบหน้าใช้งานได้ดีหรือไม่"""
    if 'photo' not in request.files:
        return jsonify({"error": "No photo provided"}), 400

    file = request.files['photo']
    file_bytes = np.frombuffer(file.read(), np.uint8)
    img = cv2.imdecode(file_bytes, cv2.IMREAD_COLOR)

    if img is None:
        return jsonify({
            "valid": False,
            "faces_found": 0,
            "message": "ไม่สามารถอ่านไฟล์รูปภาพได้"
        })

    rgb_img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)

    # Detect faces
    face_locations = face_recognition.face_locations(rgb_img, model="hog")
    face_count = len(face_locations)

    if face_count == 0:
        return jsonify({
            "valid": False,
            "faces_found": 0,
            "message": "ไม่พบใบหน้าในรูปภาพ กรุณาอัพโหลดรูปที่เห็นใบหน้าชัดเจน"
        })

    if face_count > 1:
        return jsonify({
            "valid": False,
            "faces_found": face_count,
            "message": f"พบ {face_count} ใบหน้าในรูป กรุณาอัพโหลดรูปที่มีเพียง 1 ใบหน้า"
        })

    # Check face encoding quality
    face_encodings = face_recognition.face_encodings(rgb_img, face_locations)
    if not face_encodings:
        return jsonify({
            "valid": False,
            "faces_found": 1,
            "message": "ตรวจพบใบหน้าแต่ไม่สามารถวิเคราะห์ได้ กรุณาใช้รูปที่ชัดเจนกว่านี้"
        })

    # Calculate face size ratio
    top, right, bottom, left = face_locations[0]
    face_height = bottom - top
    face_width = right - left
    img_height, img_width = img.shape[:2]
    face_ratio = (face_height * face_width) / (img_height * img_width)

    # Quality checks
    quality_notes = []
    if face_ratio < 0.02:
        quality_notes.append("ใบหน้าเล็กเกินไป ควรถ่ายใกล้กว่านี้")
    if face_height < 80 or face_width < 80:
        quality_notes.append("ความละเอียดใบหน้าต่ำ อาจส่งผลต่อการจดจำ")

    return jsonify({
        "valid": True,
        "faces_found": 1,
        "face_size": {"width": face_width, "height": face_height},
        "face_ratio": round(face_ratio * 100, 1),
        "quality_notes": quality_notes,
        "message": "พบใบหน้า 1 ใบหน้า สามารถใช้งานได้" + (
            " (คำแนะนำ: " + ", ".join(quality_notes) + ")" if quality_notes else ""
        )
    })


# ============================================================
# Face Reload API
# ============================================================
@app.route('/api/face/reload', methods=['POST'])
def api_face_reload():
    """โหลดใบหน้าใหม่จากโฟลเดอร์ images/ (เรียกหลังเพิ่ม/ลบรูป)"""
    loaded = sfr.reload_images(config.IMAGES_PATH)
    return jsonify({
        "success": True,
        "faces_loaded": loaded,
        "known_names": sfr.known_face_names,
        "message": f"โหลดใบหน้าใหม่สำเร็จ {loaded} ใบหน้า"
    })


# ============================================================
# Main
# ============================================================
if __name__ == "__main__":
    print("=" * 50)
    print("  Bunny Door System - Face Recognition Server")
    print("=" * 50)

    # อัปเดตสถานะ
    update_system_status("face_server", "ONLINE")
    update_system_status("raspberry_pi", "ONLINE")

    # เริ่ม camera threads (ข้ามถ้า ID = -1)
    if config.CAMERA_OUTSIDE_ID >= 0:
        t_outside = threading.Thread(
            target=camera_thread,
            args=(config.CAMERA_OUTSIDE_ID, "camera_outside"),
            daemon=True
        )
        t_outside.start()
    else:
        print("[Camera] camera_outside disabled (ID=-1)")

    if config.CAMERA_INSIDE_ID >= 0:
        t_inside = threading.Thread(
            target=camera_thread,
            args=(config.CAMERA_INSIDE_ID, "camera_inside"),
            daemon=True
        )
        t_inside.start()
    else:
        print("[Camera] camera_inside disabled (ID=-1)")

    print(f"[Server] Starting Flask API on {config.API_HOST}:{config.API_PORT}")

    def cleanup(signum=None, frame=None):
        print("\n[Server] Shutting down...")
        system_state["running"] = False
        # Release กล้องทั้งหมด
        for name, cap in _camera_captures.items():
            if cap.isOpened():
                cap.release()
                print(f"[Camera] {name} released")
        update_system_status("face_server", "OFFLINE")
        update_system_status("raspberry_pi", "OFFLINE")
        sys.exit(0)

    signal.signal(signal.SIGINT, cleanup)
    signal.signal(signal.SIGTERM, cleanup)

    try:
        app.run(host=config.API_HOST, port=config.API_PORT, threaded=True)
    except KeyboardInterrupt:
        cleanup()
