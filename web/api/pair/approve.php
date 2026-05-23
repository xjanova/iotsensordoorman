<?php
/**
 * Pair: approve / revoke device
 * POST { "id": N, "action": "approve" | "revoke" | "delete" }
 */
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

requireLogin();
requireCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);
$action = $input['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve', 'revoke', 'delete'], true)) {
    jsonResponse(['error' => 'invalid request'], 400);
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM paired_devices WHERE id = ?");
    $stmt->execute([$id]);
    $dev = $stmt->fetch();
    if (!$dev) jsonResponse(['error' => 'not found'], 404);

    if ($action === 'delete') {
        $del = $db->prepare("DELETE FROM paired_devices WHERE id = ?");
        $del->execute([$id]);
        jsonResponse(['success' => true, 'action' => 'deleted']);
    }

    $newStatus = $action === 'approve' ? 'TRUSTED' : 'REVOKED';
    $upd = $db->prepare("UPDATE paired_devices
        SET status = ?, approved_by = ?, approved_at = NOW()
        WHERE id = ?");
    $upd->execute([$newStatus, $_SESSION['admin_id'] ?? null, $id]);

    // เมื่อ approve: sync ค่าลง settings ทันที
    if ($action === 'approve') {
        if ($dev['role'] === 'ESP32') {
            $u = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('esp32_ip', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $u->execute([$dev['ip_address']]);
        }
        if ($dev['role'] === 'PI') {
            $u = $db->prepare("UPDATE system_status SET status = 'ONLINE', last_heartbeat = NOW(), ip_address = ? WHERE component IN ('face_server','raspberry_pi')");
            $u->execute([$dev['ip_address']]);
        }
    }

    jsonResponse(['success' => true, 'action' => $action, 'status' => $newStatus]);
} catch (PDOException $e) {
    error_log("[API pair/approve] " . $e->getMessage());
    jsonResponse(['error' => 'DB error'], 500);
}
