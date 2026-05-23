<?php
/**
 * Pair: get / regenerate pairing token
 * GET  → คืน token ปัจจุบัน
 * POST { "action": "regenerate" } → สร้างใหม่
 */
require_once __DIR__ . '/../../includes/api_auth.php';

requireLogin();

try {
    $db = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf();
        $input = json_decode(file_get_contents('php://input'), true);
        if (($input['action'] ?? '') !== 'regenerate') {
            jsonResponse(['error' => 'invalid action'], 400);
        }
        $token = bin2hex(random_bytes(16)); // 32-char hex
        $up = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('pairing_token', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $up->execute([$token]);
        // เมื่อสร้าง token ใหม่: revoke device ทั้งหมด (เพราะ token เก่าใช้ไม่ได้แล้ว)
        $db->exec("UPDATE paired_devices SET status = 'REVOKED' WHERE status != 'REVOKED'");
        jsonResponse([
            'success' => true,
            'token' => $token,
            'message' => 'token ใหม่สร้างแล้ว — อุปกรณ์เดิมต้อง re-pair',
        ]);
    } else {
        $row = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'pairing_token' LIMIT 1")->fetch();
        $token = $row['setting_value'] ?? '';
        if ($token === '') {
            // auto-generate ถ้ายังไม่มี
            $token = bin2hex(random_bytes(16));
            $up = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('pairing_token', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $up->execute([$token]);
        }
        jsonResponse(['token' => $token]);
    }
} catch (PDOException $e) {
    error_log("[API pair/token] " . $e->getMessage());
    jsonResponse(['error' => 'DB error'], 500);
}
