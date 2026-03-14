<?php
/**
 * API: จัดการพนักงาน (CRUD)
 */
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

// Require login for write operations
if ($method !== 'GET') {
    session_start();
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['error' => 'กรุณาเข้าสู่ระบบ'], 401);
    }
}

try {
    $db = getDB();

    switch ($method) {
        case 'GET':
            // Generate next emp_code
            if (isset($_GET['next_code'])) {
                $stmt = $db->query("SELECT emp_code FROM employees WHERE emp_code LIKE 'EMP%' ORDER BY emp_code DESC LIMIT 1");
                $last = $stmt->fetch();
                if ($last && preg_match('/^EMP(\d+)$/', $last['emp_code'], $m)) {
                    $next = 'EMP' . str_pad(intval($m[1]) + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $next = 'EMP001';
                }
                jsonResponse(['next_code' => $next]);
            }

            $stmt = $db->query("SELECT * FROM employees ORDER BY emp_code");
            jsonResponse($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) jsonResponse(['error' => 'Invalid data'], 400);

            // Input validation
            $empCode = trim($data['emp_code'] ?? '');
            $firstName = trim($data['first_name'] ?? '');
            $lastName = trim($data['last_name'] ?? '');
            $department = trim($data['department'] ?? '') ?: null;
            $position = trim($data['position'] ?? '') ?: null;
            $faceImage = trim($data['face_image'] ?? '') ?: null;
            $isAuthorized = intval($data['is_authorized'] ?? 1);

            if ($empCode === '' || $firstName === '' || $lastName === '') {
                jsonResponse(['error' => 'กรุณากรอก รหัสพนักงาน, ชื่อ และนามสกุล'], 400);
            }

            // Validate emp_code format (alphanumeric + dash, max 20 chars)
            if (!preg_match('/^[A-Za-z0-9\-]{1,20}$/', $empCode)) {
                jsonResponse(['error' => 'รหัสพนักงานต้องเป็นตัวอักษรภาษาอังกฤษ ตัวเลข หรือ - เท่านั้น (สูงสุด 20 ตัว)'], 400);
            }

            // Validate face_image filename (prevent path traversal)
            if ($faceImage !== null && !preg_match('/^[A-Za-z0-9_\-\.]+$/', $faceImage)) {
                jsonResponse(['error' => 'ชื่อไฟล์รูปไม่ถูกต้อง'], 400);
            }

            if (!empty($data['id'])) {
                // Update
                $id = intval($data['id']);
                $stmt = $db->prepare("UPDATE employees SET emp_code=?, first_name=?, last_name=?, department=?, position=?, face_image=?, is_authorized=? WHERE id=?");
                $stmt->execute([$empCode, $firstName, $lastName, $department, $position, $faceImage, $isAuthorized, $id]);
            } else {
                // Check duplicate emp_code
                $check = $db->prepare("SELECT id FROM employees WHERE emp_code = ?");
                $check->execute([$empCode]);
                if ($check->fetch()) {
                    jsonResponse(['error' => 'รหัสพนักงานนี้มีอยู่แล้ว'], 400);
                }

                // Insert
                $stmt = $db->prepare("INSERT INTO employees (emp_code, first_name, last_name, department, position, face_image, is_authorized) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$empCode, $firstName, $lastName, $department, $position, $faceImage, $isAuthorized]);
            }
            jsonResponse(['success' => true]);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(['error' => 'ID required'], 400);
            $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    error_log("[API employees] " . $e->getMessage());
    jsonResponse(['error' => 'เกิดข้อผิดพลาดของฐานข้อมูล'], 500);
}
