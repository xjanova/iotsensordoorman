<?php
/**
 * API: การแจ้งเตือนความผิดปกติ
 */
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') jsonResponse(['ok' => true]);

try {
    $db = getDB();

    switch ($method) {
        case 'GET':
            $limit = intval($_GET['limit'] ?? 50);
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
            if (!empty($data['resolve_id'])) {
                $stmt = $db->prepare("UPDATE anomaly_alerts SET is_resolved = 1, resolved_at = NOW() WHERE id = ?");
                $stmt->execute([$data['resolve_id']]);
                jsonResponse(['success' => true]);
            } else {
                jsonResponse(['error' => 'Invalid action'], 400);
            }
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
