<?php
/**
 * API: ตั้งค่าระบบ
 */
require_once __DIR__ . '/../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') jsonResponse(['ok' => true]);

// Pi อาจดึง settings เพื่อ sync config — อนุญาต pair token สำหรับ GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireLoginOrPairToken();
} else {
    requireLogin();
}

// Whitelist ของ key + type/range constraints
// คีย์ที่ไม่อยู่ในนี้จะถูกปฏิเสธ — กัน user ส่งคีย์มั่ว
const SETTING_SPECS = [
    'door_unlock_seconds'        => ['type' => 'int',  'min' => 1,    'max' => 60],
    'face_confidence_threshold'  => ['type' => 'int',  'min' => 1,    'max' => 100],
    'tailgate_detection'         => ['type' => 'bool'],
    'alert_unknown_face'         => ['type' => 'bool'],
    'max_persons_per_entry'      => ['type' => 'int',  'min' => 1,    'max' => 10],
    'process_every_x_frames'     => ['type' => 'int',  'min' => 1,    'max' => 30],
    'esp32_ip'                   => ['type' => 'ip'],
    'camera_outside_id'          => ['type' => 'int',  'min' => -1,   'max' => 99],
    'camera_inside_id'           => ['type' => 'int',  'min' => -1,   'max' => 99],
    'wifi_ssid'                  => ['type' => 'str',  'maxlen' => 64],
    'wifi_password'              => ['type' => 'str',  'maxlen' => 128],
    'server_url'                 => ['type' => 'url'],
    'pir_cooldown_ms'            => ['type' => 'int',  'min' => 500,  'max' => 30000],
    'heartbeat_interval_ms'      => ['type' => 'int',  'min' => 1000, 'max' => 60000],
    'door_lock_type'             => ['type' => 'enum', 'options' => ['NO', 'NC']],
    'pairing_enabled'            => ['type' => 'bool'],
];

function validateSetting(string $key, $value): array {
    if (!isset(SETTING_SPECS[$key])) return [false, 'unknown key'];
    $spec = SETTING_SPECS[$key];
    $v = is_string($value) ? trim($value) : $value;
    switch ($spec['type']) {
        case 'int':
            if (!is_numeric($v)) return [false, 'must be number'];
            $iv = intval($v);
            if (isset($spec['min']) && $iv < $spec['min']) return [false, 'too small'];
            if (isset($spec['max']) && $iv > $spec['max']) return [false, 'too large'];
            return [true, strval($iv)];
        case 'bool':
            $bv = in_array($v, [1, '1', true, 'true', 'on'], true) ? '1' : '0';
            return [true, $bv];
        case 'ip':
            if ($v === '') return [true, ''];
            if (!filter_var($v, FILTER_VALIDATE_IP)) return [false, 'invalid IP'];
            return [true, $v];
        case 'url':
            if ($v === '') return [true, ''];
            if (!filter_var($v, FILTER_VALIDATE_URL)) return [false, 'invalid URL'];
            return [true, $v];
        case 'enum':
            if (!in_array($v, $spec['options'], true)) return [false, 'invalid option'];
            return [true, $v];
        case 'str':
        default:
            $sv = strval($v);
            if (isset($spec['maxlen']) && strlen($sv) > $spec['maxlen']) return [false, 'too long'];
            return [true, $sv];
    }
}

try {
    $db = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) jsonResponse(['error' => 'Invalid data'], 400);

        // UPSERT — เพื่อให้รองรับคีย์ใหม่ที่ schema อาจยังไม่ได้ INSERT IGNORE
        $upsert = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $updated = 0;
        $errors = [];
        foreach ($data as $key => $value) {
            [$ok, $result] = validateSetting($key, $value);
            if (!$ok) {
                $errors[$key] = $result;
                continue;
            }
            $upsert->execute([$key, $result]);
            $updated++;
        }
        $resp = ['success' => true, 'updated' => $updated];
        if ($errors) $resp['errors'] = $errors;
        jsonResponse($resp);
    } else {
        $stmt = $db->query("SELECT setting_key, setting_value, description FROM settings ORDER BY id");
        $rows = $stmt->fetchAll();
        // ปกปิด pairing_token สำหรับ browser GET (สำคัญ — ห้ามให้ admin เห็นใน devtools เผลอ leak)
        // อนุญาตเฉพาะถ้ามาด้วย X-Pair-Token (Pi ที่รู้ token อยู่แล้ว)
        $hasToken = !empty($_SERVER['HTTP_X_PAIR_TOKEN']);
        if (!$hasToken) {
            foreach ($rows as &$r) {
                if (in_array($r['setting_key'], ['pairing_token', 'wifi_password'], true)) {
                    $r['setting_value'] = $r['setting_value'] === '' ? '' : '••••••••';
                }
            }
        }
        jsonResponse($rows);
    }
} catch (PDOException $e) {
    error_log("[API settings] " . $e->getMessage());
    jsonResponse(['error' => 'เกิดข้อผิดพลาดของฐานข้อมูล'], 500);
}
