<?php
/**
 * API: จัดการพนักงาน (CRUD)
 */
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

try {
    $db = getDB();

    switch ($method) {
        case 'GET':
            $stmt = $db->query("SELECT * FROM employees ORDER BY emp_code");
            jsonResponse($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) jsonResponse(['error' => 'Invalid data'], 400);

            if (!empty($data['id'])) {
                // Update
                $stmt = $db->prepare("UPDATE employees SET emp_code=?, first_name=?, last_name=?, department=?, position=?, face_image=?, is_authorized=? WHERE id=?");
                $stmt->execute([
                    $data['emp_code'], $data['first_name'], $data['last_name'],
                    $data['department'] ?? null, $data['position'] ?? null,
                    $data['face_image'] ?? null, $data['is_authorized'] ?? 1,
                    $data['id']
                ]);
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO employees (emp_code, first_name, last_name, department, position, face_image, is_authorized) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([
                    $data['emp_code'], $data['first_name'], $data['last_name'],
                    $data['department'] ?? null, $data['position'] ?? null,
                    $data['face_image'] ?? null, $data['is_authorized'] ?? 1
                ]);
            }
            jsonResponse(['success' => true]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) jsonResponse(['error' => 'ID required'], 400);
            $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
