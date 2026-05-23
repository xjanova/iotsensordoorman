"""
Bunny Door Local Store
======================
Offline-first cache สำหรับให้ Pi ทำงานได้แม้ DB (Laragon) ตาย:

- employees.json     — สำเนา employees table (sync จาก DB ทุก 5 นาที)
- pending_logs.jsonl — access_logs ที่ค้างส่งไป DB (push เมื่อ DB กลับมา)
- pending_emp.jsonl  — พนักงานใหม่ที่ scan offline (push เมื่อ DB กลับมา)

ทุกไฟล์อยู่ที่ ~/bunny-door/cache/ (mkdir อัตโนมัติ)
"""
import json
import os
import threading
import time
from pathlib import Path

_CACHE_DIR = Path.home() / "bunny-door" / "cache"
_CACHE_DIR.mkdir(parents=True, exist_ok=True)

EMP_FILE = _CACHE_DIR / "employees.json"
PENDING_LOG_FILE = _CACHE_DIR / "pending_logs.jsonl"
PENDING_EMP_FILE = _CACHE_DIR / "pending_emp.jsonl"

_lock_emp = threading.Lock()
_lock_log = threading.Lock()


# ============================================================
# Employees cache (sync จาก DB)
# ============================================================
def cache_employees(rows: list[dict]) -> None:
    """เก็บ employees rows ลง JSON (เรียกหลัง load จาก DB สำเร็จ)"""
    try:
        with _lock_emp:
            with open(EMP_FILE, "w", encoding="utf-8") as f:
                json.dump(rows, f, ensure_ascii=False, indent=2)
    except Exception as e:
        print(f"[LocalStore] cache_employees error: {e}")


def load_cached_employees() -> list[dict]:
    """โหลด employees จาก cache ไฟล์"""
    try:
        if not EMP_FILE.exists():
            return []
        with _lock_emp:
            with open(EMP_FILE, encoding="utf-8") as f:
                return json.load(f)
    except Exception as e:
        print(f"[LocalStore] load_cached_employees error: {e}")
        return []


def find_employee_by_name(name: str) -> dict | None:
    """ค้น employee จาก local cache — รองรับ face_image, emp_code, ชื่อไฟล์"""
    rows = load_cached_employees()
    for r in rows:
        if not r.get("is_authorized"):
            continue
        # match face_image (ทั้งชื่อเต็มและตัด extension)
        face = r.get("face_image") or ""
        if face.startswith(name):
            return r
        face_no_ext = os.path.splitext(face)[0]
        if face_no_ext == name:
            return r
        # match emp_code
        if r.get("emp_code") == name:
            return r
    return None


# ============================================================
# Pending access logs queue (offline)
# ============================================================
def queue_access_log(
    employee_id, direction, method, confidence,
    camera_id, sensor_id, snapshot, authorized,
    ts: float | None = None,
) -> None:
    """Append access log ลง pending queue"""
    entry = {
        "ts": ts or time.time(),
        "employee_id": employee_id,
        "direction": direction,
        "method": method,
        "confidence": confidence,
        "camera_id": camera_id,
        "sensor_id": sensor_id,
        "snapshot": snapshot,
        "authorized": authorized,
    }
    try:
        with _lock_log:
            with open(PENDING_LOG_FILE, "a", encoding="utf-8") as f:
                f.write(json.dumps(entry, ensure_ascii=False) + "\n")
    except Exception as e:
        print(f"[LocalStore] queue_access_log error: {e}")


def flush_access_logs(db_writer) -> int:
    """ส่ง pending logs ไปยัง DB ผ่าน callback db_writer(entry)→bool
       คืนจำนวน rows ที่ flush สำเร็จ
       ถ้า db_writer return False → ไม่ลบ row, retry รอบหน้า
    """
    if not PENDING_LOG_FILE.exists():
        return 0
    with _lock_log:
        try:
            with open(PENDING_LOG_FILE, encoding="utf-8") as f:
                lines = f.readlines()
        except Exception:
            return 0

        remaining = []
        flushed = 0
        for line in lines:
            line = line.strip()
            if not line:
                continue
            try:
                entry = json.loads(line)
            except Exception:
                continue
            try:
                ok = db_writer(entry)
            except Exception as e:
                ok = False
                print(f"[LocalStore] flush db_writer exception: {e}")
            if ok:
                flushed += 1
            else:
                remaining.append(line)

        try:
            with open(PENDING_LOG_FILE, "w", encoding="utf-8") as f:
                for line in remaining:
                    f.write(line + "\n")
        except Exception as e:
            print(f"[LocalStore] flush rewrite error: {e}")

    return flushed


# ============================================================
# Pending new employees (scan offline)
# ============================================================
def queue_new_employee(
    emp_code: str,
    first_name: str,
    last_name: str,
    face_image: str,
    department: str = "",
    position: str = "",
) -> None:
    """บันทึก scan ใหม่ลง queue + เพิ่มเข้า employees cache เลย
       ให้ recognize ได้ก่อนแม้ DB ยังไม่ sync"""
    entry = {
        "ts": time.time(),
        "emp_code": emp_code,
        "first_name": first_name,
        "last_name": last_name,
        "department": department,
        "position": position,
        "face_image": face_image,
        "is_authorized": 1,
    }
    try:
        with _lock_emp:
            with open(PENDING_EMP_FILE, "a", encoding="utf-8") as f:
                f.write(json.dumps(entry, ensure_ascii=False) + "\n")
        # เพิ่มเข้า employees cache เลย — ใช้ recognize ได้ทันที
        rows = load_cached_employees()
        # remove existing emp_code
        rows = [r for r in rows if r.get("emp_code") != emp_code]
        rows.append({
            "id": -1,  # placeholder (จะอัพเดทเมื่อ sync DB)
            "emp_code": emp_code,
            "first_name": first_name,
            "last_name": last_name,
            "department": department,
            "position": position,
            "face_image": face_image,
            "is_authorized": 1,
        })
        cache_employees(rows)
    except Exception as e:
        print(f"[LocalStore] queue_new_employee error: {e}")


def flush_new_employees(db_writer) -> int:
    """ส่ง pending employees ไป DB ผ่าน db_writer(entry)→new_id|None
       คืนจำนวน rows ที่ flush สำเร็จ"""
    if not PENDING_EMP_FILE.exists():
        return 0
    with _lock_emp:
        try:
            with open(PENDING_EMP_FILE, encoding="utf-8") as f:
                lines = f.readlines()
        except Exception:
            return 0

        remaining = []
        flushed = 0
        for line in lines:
            line = line.strip()
            if not line:
                continue
            try:
                entry = json.loads(line)
            except Exception:
                continue
            try:
                new_id = db_writer(entry)
            except Exception as e:
                new_id = None
                print(f"[LocalStore] flush_emp db_writer exception: {e}")
            if new_id:
                flushed += 1
                # อัพเดท employees cache: update id ของ row นี้
                rows = load_cached_employees()
                for r in rows:
                    if r.get("emp_code") == entry.get("emp_code"):
                        r["id"] = new_id
                cache_employees(rows)
            else:
                remaining.append(line)

        try:
            with open(PENDING_EMP_FILE, "w", encoding="utf-8") as f:
                for line in remaining:
                    f.write(line + "\n")
        except Exception as e:
            print(f"[LocalStore] flush_emp rewrite error: {e}")

    return flushed


def stats() -> dict:
    """รายงานจำนวน row ใน cache + pending"""
    def _count_lines(p):
        try:
            return sum(1 for _ in open(p, encoding="utf-8") if _.strip())
        except Exception:
            return 0
    return {
        "employees_cached": len(load_cached_employees()),
        "pending_logs": _count_lines(PENDING_LOG_FILE),
        "pending_employees": _count_lines(PENDING_EMP_FILE),
    }
