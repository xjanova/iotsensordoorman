<?php
/**
 * API: ตั้งค่าระบบ
 */
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') jsonResponse(['ok' => true]);

try {
    $db = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) jsonResponse(['error' => 'Invalid data'], 400);

        // Whitelist: only allow updating keys that exist in the database
        $existing = $db->query("SELECT setting_key FROM settings")->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $updated = 0;
        foreach ($data as $key => $value) {
            if (in_array($key, $existing, true)) {
                $stmt->execute([strval($value), $key]);
                $updated++;
            }
        }
        jsonResponse(['success' => true, 'updated' => $updated]);
    } else {
        $stmt = $db->query("SELECT setting_key, setting_value, description FROM settings ORDER BY id");
        jsonResponse($stmt->fetchAll());
    }
} catch (PDOException $e) {
    error_log("[API settings] " . $e->getMessage());
    jsonResponse(['error' => 'เกิดข้อผิดพลาดของฐานข้อมูล'], 500);
}
