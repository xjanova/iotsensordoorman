<?php
/**
 * Pair: ping endpoint
 * ใช้สำหรับให้ Pi/อุปกรณ์อื่นค้นหา web server บน LAN (subnet scan)
 * ไม่ต้องมี auth — ตอบแค่ metadata ไม่มีข้อมูลละเอียดอ่อน
 */
require_once __DIR__ . '/../../config.php';

// pairing flags
$pairingOn = false;
$autoOn = false;
try {
    $db = getDB();
    $rows = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('pairing_enabled','auto_pairing_enabled')")->fetchAll();
    foreach ($rows as $r) {
        if ($r['setting_key'] === 'pairing_enabled')      $pairingOn = $r['setting_value'] === '1';
        if ($r['setting_key'] === 'auto_pairing_enabled') $autoOn    = $r['setting_value'] === '1';
    }
} catch (Throwable $e) {
    // เงียบไว้ — ถ้า DB ล่ม ก็ตอบว่าไม่พร้อม pair
}

jsonResponse([
    'ok' => true,
    'service' => 'bunny-door-web',
    'version' => APP_VERSION,
    'pairing_enabled' => $pairingOn,
    'auto_pairing_enabled' => $autoOn,
    'endpoints' => [
        'announce'    => 'api/pair/announce.php',
        'credentials' => 'api/pair/credentials.php',
    ],
]);
