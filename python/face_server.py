"""
Bunny Door System - Face Recognition + API Server
===================================================
เทคนิคที่ปรับปรุงจากต้นฉบับ:
1. ใช้ Threading แยกกล้องแต่ละตัว (ไม่บล็อกกัน)
2. เพิ่ม Motion-triggered recognition (ประหยัด CPU)
3. เพิ่ม Tailgating detection (ตรวจจับการแอบเข้าตาม)
4. เพิ่ม Snapshot บันทึกภาพทุกครั้งที่มีการเข้า-ออก
5. Flask API สำหรับสื่อสารกับ PHP Dashboard
6. ใช้ BackgroundSubtractor MOG2 ตรวจจับการเคลื่อนไหว
"""

import cv2
import numpy as np
import os
import time
import json
import threading
import requests
import mysql.connector
from datetime import datetime
from collections import Counter
from flask import Flask, Response, jsonify, request
from flask_cors import CORS
from simple_facerec import SimpleFaceRec
import config

# ============================================================
# Initialize
# ============================================================
app = Flask(__name__)
CORS(app, origins=["http://localhost", "http://127.0.0.1"])

# Face Recognition
sfr = SimpleFaceRec(
    model=config.FACE_MODEL,
    tolerance=config.FACE_CONFIDENCE_THRESHOLD,
    frame_resizing=config.FRAME_RESIZING
)
sfr.load_encoding_images(config.IMAGES_PATH)

# Background Subtractors สำหรับ motion detection (แต่ละกล้อง)
bg_sub_outside = cv2.createBackgroundSubtractorMOG2(
    history=500, varThreshold=50, detectShadows=False
)
bg_sub_inside = cv2.createBackgroundSubtractorMOG2(
    history=500, varThreshold=50, detectShadows=False
)

# State
system_state = {
    "camera_outside": {"frame": None, "faces": [], "motion": False, "lock": threading.Lock()},
    "camera_inside": {"frame": None, "faces": [], "motion": False, "lock": threading.Lock()},
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
def camera_thread(camera_id, cam_name, bg_subtractor):
    """Thread สำหรับประมวลผลกล้องแต่ละตัว"""
    print(f"[Camera] Starting {cam_name} (ID: {camera_id})")

    cap = cv2.VideoCapture(camera_id)
    if not cap.isOpened():
        print(f"[Camera] Failed to open {cam_name}")
        update_system_status(cam_name, "OFFLINE")
        return

    cap.set(cv2.CAP_PROP_FRAME_WIDTH, config.CAMERA_WIDTH)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, config.CAMERA_HEIGHT)
    update_system_status(cam_name, "ONLINE")

    frame_count = 0
    last_faces = []
    last_names = []
    last_confidences = []

    while system_state["running"]:
        ret, frame = cap.read()
        if not ret:
            time.sleep(0.1)
            continue

        frame_count += 1

        # Motion detection ด้วย Background Subtraction
        fg_mask = bg_subtractor.apply(frame)
        motion_area = cv2.countNonZero(fg_mask)
        has_motion = motion_area > 5000  # threshold

        cam_state = system_state[cam_name]
        cam_state["motion"] = has_motion

        # Face recognition เฉพาะเมื่อมี motion หรือทุก X เฟรม
        if has_motion or frame_count % config.PROCESS_EVERY_X_FRAMES == 0:
            faces, names, confidences = sfr.detect_known_faces(frame)
            last_faces = faces
            last_names = names
            last_confidences = confidences

            # ประมวลผลใบหน้าที่ตรวจพบ
            if names:
                process_detected_faces(
                    frame, faces, names, confidences,
                    camera_id=1 if cam_name == "camera_outside" else 2,
                    cam_name=cam_name
                )

        # วาดกรอบใบหน้าบนภาพ
        display_frame = frame.copy()
        for (top, right, bottom, left), name, conf in zip(last_faces, last_names, last_confidences):
            color = (0, 255, 0) if name != "Unknown" else (0, 0, 255)
            cv2.rectangle(display_frame, (left, top), (right, bottom), color, 2)
            label = f"{name} ({conf}%)" if conf > 0 else "Unknown"
            cv2.putText(display_frame, label, (left, top - 10),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)

        # แสดง motion indicator
        if has_motion:
            cv2.putText(display_frame, "MOTION", (10, 30),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 255), 2)

        # แสดงจำนวนคน
        cv2.putText(display_frame, f"Faces: {len(last_names)}", (10, 60),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)

        # อัปเดต frame สำหรับ streaming
        with cam_state["lock"]:
            cam_state["frame"] = display_frame
            cam_state["faces"] = list(zip(last_names, last_confidences))

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
# Video Streaming
# ============================================================
def generate_stream(cam_name):
    """สร้าง MJPEG stream สำหรับ web dashboard"""
    while system_state["running"]:
        cam_state = system_state[cam_name]
        with cam_state["lock"]:
            frame = cam_state["frame"]

        if frame is not None:
            _, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 70])
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
        else:
            time.sleep(0.1)


# ============================================================
# Flask API Routes
# ============================================================

@app.route('/')
def index():
    return jsonify({"system": "Bunny Door System", "status": "running"})


@app.route('/api/stream/outside')
def stream_outside():
    return Response(generate_stream("camera_outside"),
                    mimetype='multipart/x-mixed-replace; boundary=frame')


@app.route('/api/stream/inside')
def stream_inside():
    return Response(generate_stream("camera_inside"),
                    mimetype='multipart/x-mixed-replace; boundary=frame')


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
            args=(config.CAMERA_OUTSIDE_ID, "camera_outside", bg_sub_outside),
            daemon=True
        )
        t_outside.start()
    else:
        print("[Camera] camera_outside disabled (ID=-1)")

    if config.CAMERA_INSIDE_ID >= 0:
        t_inside = threading.Thread(
            target=camera_thread,
            args=(config.CAMERA_INSIDE_ID, "camera_inside", bg_sub_inside),
            daemon=True
        )
        t_inside.start()
    else:
        print("[Camera] camera_inside disabled (ID=-1)")

    print(f"[Server] Starting Flask API on {config.API_HOST}:{config.API_PORT}")

    try:
        app.run(host=config.API_HOST, port=config.API_PORT, threaded=True)
    except KeyboardInterrupt:
        print("\n[Server] Shutting down...")
        system_state["running"] = False
        update_system_status("face_server", "OFFLINE")
        update_system_status("raspberry_pi", "OFFLINE")
