<?php
/**
 * API: ประวัติการเข้า-ออก
 */
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') jsonResponse(['ok' => true]);

try {
    $db = getDB();
    $limit = min(max(intval($_GET['limit'] ?? 100), 1), 1000);
    $date = $_GET['date'] ?? null;

    $sql = "SELECT al.*, e.first_name, e.last_name, e.emp_code, e.department
            FROM access_logs al
            LEFT JOIN employees e ON al.employee_id = e.id";
    $params = [];

    if ($date) {
        // Validate date format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            jsonResponse(['error' => 'รูปแบบวันที่ไม่ถูกต้อง (YYYY-MM-DD)'], 400);
        }
        $sql .= " WHERE DATE(al.created_at) = ?";
        $params[] = $date;
    }

    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
} catch (PDOException $e) {
    error_log("[API access_logs] " . $e->getMessage());
    jsonResponse(['error' => 'เกิดข้อผิดพลาดของฐานข้อมูล'], 500);
}
