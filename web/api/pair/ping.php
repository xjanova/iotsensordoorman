<?php
/**
 * Pair: ping endpoint
 * ใช้สำหรับให้ Pi/อุปกรณ์อื่นค้นหา web server บน LAN (subnet scan)
 * ไม่ต้องมี auth — ตอบแค่ metadata ไม่มีข้อมูลละเอียดอ่อน
 */
require_once __DIR__ . '/../../config.php';

// pairing_enabled flag ดูจาก settings
$pairingOn = false;
try {
    $db = getDB();
    $row = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'pairing_enabled' LIMIT 1")->fetch();
    $pairingOn = $row && $row['setting_value'] === '1';
} catch (Throwable $e) {
    // เงียบไว้ — ถ้า DB ล่ม ก็ตอบว่าไม่พร้อม pair
}

jsonResponse([
    'ok' => true,
    'service' => 'bunny-door-web',
    'version' => APP_VERSION,
    'pairing_enabled' => $pairingOn,
    'endpoints' => [
        'announce' => 'api/pair/announce.php',
    ],
]);
