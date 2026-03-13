-- ============================================================
-- Bunny Door System - Database Schema
-- ระบบอนุญาตการเข้า-ออกห้องสโตร์
-- Enhanced: Dual Sensors + Dual Cameras + Anomaly Detection
-- ============================================================

CREATE DATABASE IF NOT EXISTS bunny_door CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bunny_door;

-- ============================================================
-- ตาราง: พนักงาน (Employees)
-- ============================================================
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'รหัสพนักงาน',
    first_name VARCHAR(100) NOT NULL COMMENT 'ชื่อ',
    last_name VARCHAR(100) NOT NULL COMMENT 'นามสกุล',
    department VARCHAR(100) DEFAULT NULL COMMENT 'แผนก',
    position VARCHAR(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
    face_image VARCHAR(255) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปใบหน้า',
    is_authorized TINYINT(1) DEFAULT 1 COMMENT 'สิทธิ์เข้า-ออก (1=อนุญาต, 0=ไม่อนุญาต)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_emp_code (emp_code),
    INDEX idx_authorized (is_authorized)
) ENGINE=InnoDB COMMENT='ข้อมูลพนักงาน';

-- ============================================================
-- ตาราง: ประวัติการเข้า-ออก (Access Logs)
-- ============================================================
CREATE TABLE IF NOT EXISTS access_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT DEFAULT NULL COMMENT 'FK พนักงาน (NULL = ไม่รู้จัก)',
    direction ENUM('IN','OUT') NOT NULL COMMENT 'ทิศทาง เข้า/ออก',
    method ENUM('FACE','MANUAL','EMERGENCY') DEFAULT 'FACE' COMMENT 'วิธีการเข้า',
    confidence DECIMAL(5,2) DEFAULT NULL COMMENT 'ความมั่นใจ (%)',
    camera_id TINYINT DEFAULT 1 COMMENT 'กล้องที่ตรวจจับ (1=นอก, 2=ใน)',
    sensor_triggered TINYINT DEFAULT NULL COMMENT 'เซ็นเซอร์ที่ทำงาน (1=นอก, 2=ใน)',
    snapshot_path VARCHAR(255) DEFAULT NULL COMMENT 'path รูปถ่ายขณะเข้า-ออก',
    is_authorized TINYINT(1) DEFAULT 0 COMMENT 'ได้รับอนุญาตหรือไม่',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_direction (direction),
    INDEX idx_created (created_at),
    INDEX idx_employee (employee_id)
) ENGINE=InnoDB COMMENT='ประวัติการเข้า-ออก';

-- ============================================================
-- ตาราง: การแจ้งเตือนความผิดปกติ (Anomaly Alerts)
-- ============================================================
CREATE TABLE IF NOT EXISTS anomaly_alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM(
        'UNKNOWN_FACE',
        'TAILGATING',
        'FORCED_ENTRY',
        'SENSOR_MISMATCH',
        'MULTI_PERSON',
        'NO_FACE_DETECTED'
    ) NOT NULL COMMENT 'ประเภทความผิดปกติ',
    severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'MEDIUM',
    description TEXT COMMENT 'รายละเอียด',
    camera_id TINYINT DEFAULT NULL,
    snapshot_path VARCHAR(255) DEFAULT NULL COMMENT 'รูปถ่ายขณะเกิดเหตุ',
    is_resolved TINYINT(1) DEFAULT 0 COMMENT 'แก้ไขแล้วหรือยัง',
    resolved_by INT DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_resolved (is_resolved),
    INDEX idx_created (created_at)
) ENGINE=InnoDB COMMENT='การแจ้งเตือนความผิดปกติ';

-- ============================================================
-- ตาราง: สถานะระบบ (System Status)
-- ============================================================
CREATE TABLE IF NOT EXISTS system_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component VARCHAR(50) NOT NULL UNIQUE COMMENT 'ชื่อส่วนประกอบ',
    status ENUM('ONLINE','OFFLINE','ERROR') DEFAULT 'OFFLINE',
    last_heartbeat DATETIME DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    extra_info JSON DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='สถานะอุปกรณ์ในระบบ';

-- ============================================================
-- ตาราง: ผู้ดูแลระบบ (Admin Users)
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'ชื่อผู้ใช้',
    password_hash VARCHAR(255) NOT NULL COMMENT 'รหัสผ่าน (bcrypt)',
    display_name VARCHAR(100) COMMENT 'ชื่อที่แสดง',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='ผู้ดูแลระบบ';

-- ============================================================
-- ตาราง: ตั้งค่าระบบ (Settings)
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='ค่าตั้งระบบ';

-- ============================================================
-- ข้อมูลเริ่มต้น
-- ============================================================

-- สถานะระบบเริ่มต้น
INSERT INTO system_status (component, status) VALUES
('camera_outside', 'OFFLINE'),
('camera_inside', 'OFFLINE'),
('sensor_outside', 'OFFLINE'),
('sensor_inside', 'OFFLINE'),
('esp32', 'OFFLINE'),
('raspberry_pi', 'OFFLINE'),
('face_server', 'OFFLINE');

-- ค่าตั้งระบบเริ่มต้น
INSERT INTO settings (setting_key, setting_value, description) VALUES
('door_unlock_seconds', '7', 'ระยะเวลาปลดล็อกประตู (วินาที)'),
('face_confidence_threshold', '60', 'ค่า confidence ขั้นต่ำสำหรับอนุญาต (%)'),
('tailgate_detection', '1', 'เปิดใช้ตรวจจับ Tailgating (1=เปิด, 0=ปิด)'),
('alert_unknown_face', '1', 'แจ้งเตือนเมื่อพบใบหน้าที่ไม่รู้จัก'),
('max_persons_per_entry', '1', 'จำนวนคนสูงสุดต่อการเข้า 1 ครั้ง'),
('process_every_x_frames', '5', 'ประมวลผลทุกกี่เฟรม'),
('esp32_ip', '192.168.1.100', 'IP Address ของ ESP32'),
('camera_outside_id', '0', 'Camera ID กล้องด้านนอก'),
('camera_inside_id', '1', 'Camera ID กล้องด้านใน');

-- พนักงานตัวอย่าง
INSERT INTO employees (emp_code, first_name, last_name, department, position, face_image, is_authorized) VALUES
('EMP001', 'กุลธิดา', 'รักพวงทอง', 'วิศวกรรม', 'นักศึกษา', 'kulthida.jpg', 1),
('EMP002', 'ณัฐนัน', 'รอดแก้ว', 'วิศวกรรม', 'นักศึกษา', 'nattanan.jpg', 1),
('EMP003', 'ณัฐพล', 'งามสอาด', 'วิศวกรรม', 'นักศึกษา', 'nattapon.jpg', 1),
('EMP004', 'สรายุทธ', 'แสงบุญลือ', 'วิศวกรรม', 'นักศึกษา', 'sarayut.jpg', 1);
