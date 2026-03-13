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

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($data as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        jsonResponse(['success' => true]);
    } else {
        $stmt = $db->query("SELECT setting_key, setting_value, description FROM settings ORDER BY id");
        jsonResponse($stmt->fetchAll());
    }
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
