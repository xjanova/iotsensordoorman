<?php
/**
 * API: การแจ้งเตือนความผิดปกติ
 */
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') jsonResponse(['ok' => true]);

// Require login for write operations
if ($method === 'POST') {
    session_start();
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['error' => 'กรุณาเข้าสู่ระบบ'], 401);
    }
}

try {
    $db = getDB();

    switch ($method) {
        case 'GET':
            $limit = min(max(intval($_GET['limit'] ?? 50), 1), 500);
            $resolved = $_GET['resolved'] ?? null;

            $sql = "SELECT * FROM anomaly_alerts";
            $params = [];

            if ($resolved !== null) {
                $sql .= " WHERE is_resolved = ?";
                $params[] = intval($resolved);
            }

            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) jsonResponse(['error' => 'Invalid data'], 400);

            if (!empty($data['resolve_id'])) {
                $resolveId = intval($data['resolve_id']);
                if ($resolveId <= 0) jsonResponse(['error' => 'Invalid ID'], 400);

                $stmt = $db->prepare("UPDATE anomaly_alerts SET is_resolved = 1, resolved_at = NOW() WHERE id = ?");
                $stmt->execute([$resolveId]);
                jsonResponse(['success' => true]);
            } else {
                jsonResponse(['error' => 'Invalid action'], 400);
            }
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    error_log("[API alerts] " . $e->getMessage());
    jsonResponse(['error' => 'เกิดข้อผิดพลาดของฐานข้อมูล'], 500);
}
