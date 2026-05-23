<?php
/**
 * Pair: announce endpoint
 * Pi/ESP32 ส่งข้อมูลตัวเอง (mac, ip, role) มาเพื่อขอ pair กับ web
 *
 * POST + Header: X-Pair-Token: <pairing_token>
 * Body JSON:
 *   {
 *     "role": "PI" | "ESP32",
 *     "device_id": "AA:BB:CC:DD:EE:FF",
 *     "ip": "192.168.1.50",
 *     "hostname": "raspberrypi",
 *     "port": 5000,
 *     "extra": { "fw": "1.0.0", "caps": ["camera","face"] }
 *   }
 *
 * Response:
 *   { "ok":true, "status":"PENDING"|"TRUSTED"|"REVOKED", "id":N }
 */
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

// Zero-Config Auto-Pair:
//   - ถ้ามี X-Pair-Token → verify ตามปกติ (secure mode)
//   - ถ้าไม่มี token แต่ auto_pairing_enabled=1 → ปล่อยผ่าน
//     (device จะถูกสร้างเป็น PENDING ให้ admin approve เอง)
$hasToken = !empty($_SERVER['HTTP_X_PAIR_TOKEN']);
if ($hasToken) {
    requirePairToken();
} else {
    try {
        $db0 = getDB();
        $row = $db0->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_pairing_enabled' LIMIT 1")->fetch();
        $autoOn = $row && $row['setting_value'] === '1';
    } catch (Throwable $e) {
        $autoOn = false;
    }
    if (!$autoOn) {
        jsonResponse(['error' => 'pairing token required (auto_pairing disabled)'], 401);
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonResponse(['error' => 'Invalid JSON'], 400);
}

$role = strtoupper(trim($input['role'] ?? ''));
$deviceId = trim($input['device_id'] ?? '');
$ip = trim($input['ip'] ?? '');
$hostname = trim($input['hostname'] ?? '');
$port = isset($input['port']) ? intval($input['port']) : null;
$extra = isset($input['extra']) ? $input['extra'] : null;

// Validate
if (!in_array($role, ['PI', 'ESP32', 'OTHER'], true)) {
    jsonResponse(['error' => 'invalid role'], 400);
}
if ($deviceId === '' || strlen($deviceId) > 64) {
    jsonResponse(['error' => 'invalid device_id'], 400);
}
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    jsonResponse(['error' => 'invalid ip'], 400);
}
if ($hostname !== '' && !preg_match('/^[A-Za-z0-9._\-]{1,100}$/', $hostname)) {
    jsonResponse(['error' => 'invalid hostname'], 400);
}
if ($port !== null && ($port < 1 || $port > 65535)) {
    jsonResponse(['error' => 'invalid port'], 400);
}

try {
    $db = getDB();

    // ตรวจ pairing_enabled flag
    $row = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'pairing_enabled' LIMIT 1")->fetch();
    $pairingOn = $row && $row['setting_value'] === '1';

    // หาว่ามี device นี้แล้วหรือยัง
    $stmt = $db->prepare("SELECT id, status FROM paired_devices WHERE role = ? AND device_id = ? LIMIT 1");
    $stmt->execute([$role, $deviceId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // ถ้าโดน REVOKED ไปแล้ว → ไม่ยอมรับ
        if ($existing['status'] === 'REVOKED') {
            jsonResponse(['ok' => false, 'status' => 'REVOKED', 'message' => 'device ถูก revoke'], 403);
        }
        // Update ip/hostname/port/extra + last_seen (ON UPDATE CURRENT_TIMESTAMP)
        $upd = $db->prepare("UPDATE paired_devices
            SET ip_address = ?, hostname = ?, port = ?, extra = ?
            WHERE id = ?");
        $upd->execute([$ip, $hostname ?: null, $port, $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null, $existing['id']]);

        // ถ้า device เป็น TRUSTED + role=ESP32 → sync IP ลง settings.esp32_ip ให้อัตโนมัติ
        if ($existing['status'] === 'TRUSTED' && $role === 'ESP32') {
            $u2 = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('esp32_ip', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $u2->execute([$ip]);
        }
        // ถ้า device เป็น TRUSTED + role=PI → sync ลง system_status.face_server
        if ($existing['status'] === 'TRUSTED' && $role === 'PI') {
            $u2 = $db->prepare("UPDATE system_status SET status = 'ONLINE', last_heartbeat = NOW(), ip_address = ? WHERE component IN ('face_server','raspberry_pi')");
            $u2->execute([$ip]);
        }

        jsonResponse([
            'ok' => true,
            'status' => $existing['status'],
            'id' => intval($existing['id']),
        ]);
    } else {
        // ใหม่ — ต้องเปิด pairing_enabled
        if (!$pairingOn) {
            jsonResponse(['ok' => false, 'status' => 'BLOCKED', 'message' => 'pairing ปิดอยู่'], 403);
        }
        $ins = $db->prepare("INSERT INTO paired_devices
            (role, device_id, ip_address, hostname, port, extra, status)
            VALUES (?, ?, ?, ?, ?, ?, 'PENDING')");
        $ins->execute([
            $role, $deviceId, $ip,
            $hostname ?: null, $port,
            $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
        ]);
        jsonResponse([
            'ok' => true,
            'status' => 'PENDING',
            'id' => intval($db->lastInsertId()),
        ]);
    }
} catch (PDOException $e) {
    error_log("[API pair/announce] " . $e->getMessage());
    jsonResponse(['error' => 'DB error'], 500);
}
