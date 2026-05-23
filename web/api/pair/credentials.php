<?php
/**
 * Pair: credentials endpoint
 * Pi (TRUSTED) ขอ DB credentials + pairing token หลัง paired แล้ว
 *
 * GET /api/pair/credentials.php?role=PI&device_id=AA:BB:...
 *
 * เงื่อนไข:
 *   1. settings.db_share_enabled = 1
 *   2. device_id ต้องมีอยู่ในตาราง paired_devices + status = TRUSTED
 *   3. ที่ source IP ต้องตรงกับ ip_address ใน DB (กัน hijack จาก IP อื่น)
 *
 * Response:
 *   {
 *     "ok": true,
 *     "db": {
 *       "host": "192.168.1.148",   // ที่ web รัน
 *       "port": 3306,
 *       "user": "root",
 *       "password": "",
 *       "name": "bunny_door"
 *     },
 *     "pairing_token": "...",
 *     "esp32_ip": "192.168.1.131"
 *   }
 */
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'GET required'], 405);
}

$role     = strtoupper(trim($_GET['role'] ?? ''));
$deviceId = trim($_GET['device_id'] ?? '');
$srcIp    = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($role, ['PI','ESP32'], true) || $deviceId === '') {
    jsonResponse(['error' => 'invalid request'], 400);
}

try {
    $db = getDB();

    // เช็คว่า db_share_enabled
    $row = $db->query("SELECT setting_value FROM settings WHERE setting_key='db_share_enabled' LIMIT 1")->fetch();
    $shareOn = $row && $row['setting_value'] === '1';
    if (!$shareOn) {
        jsonResponse(['error' => 'db_share disabled'], 403);
    }

    // หา device + เช็ค TRUSTED + IP match
    $stmt = $db->prepare("SELECT id, status, ip_address FROM paired_devices WHERE role=? AND device_id=? LIMIT 1");
    $stmt->execute([$role, $deviceId]);
    $dev = $stmt->fetch();
    if (!$dev) {
        jsonResponse(['error' => 'device not paired'], 404);
    }
    if ($dev['status'] !== 'TRUSTED') {
        jsonResponse(['error' => 'device not trusted yet — admin ต้อง approve ก่อน', 'status' => $dev['status']], 403);
    }
    // กัน hijack — IP source ต้องตรงกับที่ลงทะเบียนไว้
    if ($dev['ip_address'] !== $srcIp) {
        // อัปเดต ip_address ใหม่ (อาจเป็นกรณี DHCP เปลี่ยน) แล้วยอม
        $upd = $db->prepare("UPDATE paired_devices SET ip_address=? WHERE id=?");
        $upd->execute([$srcIp, $dev['id']]);
    }

    // ดึง DB creds จาก config.php (ค่าที่ web ใช้เชื่อม)
    // — Pi ที่ TRUSTED จะใช้ค่าเดียวกัน
    $dbHost = DB_HOST === 'localhost' || DB_HOST === '127.0.0.1'
        ? ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1')   // ให้ Pi ใช้ IP web จริง ไม่ใช่ localhost
        : DB_HOST;

    // อ่าน pairing_token + esp32_ip
    $set = [];
    $r = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('pairing_token','esp32_ip')")->fetchAll();
    foreach ($r as $row) $set[$row['setting_key']] = $row['setting_value'];

    jsonResponse([
        'ok' => true,
        'db' => [
            'host'     => $dbHost,
            'port'     => DB_PORT,
            'user'     => DB_USER,
            'password' => DB_PASS,
            'name'     => DB_NAME,
        ],
        'pairing_token' => $set['pairing_token'] ?? '',
        'esp32_ip'      => $set['esp32_ip'] ?? '',
    ]);
} catch (PDOException $e) {
    error_log("[API pair/credentials] " . $e->getMessage());
    jsonResponse(['error' => 'DB error'], 500);
}
