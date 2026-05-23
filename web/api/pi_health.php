<?php
/**
 * Proxy: ดึงข้อมูล Pi health จาก face_server (แก้ปัญหา CORS)
 */
require_once __DIR__ . '/../includes/api_auth.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// ลำดับ: ใช้ Pi จาก paired_devices (Auto-Pair, TRUSTED) ก่อน → fall back FACE_SERVER_URL
$url = FACE_SERVER_URL . '/api/system/health';
try {
    $db = getDB();
    $stmt = $db->query("SELECT ip_address, port FROM paired_devices
                        WHERE role = 'PI' AND status = 'TRUSTED'
                        ORDER BY last_seen DESC LIMIT 1");
    $row = $stmt->fetch();
    if ($row) {
        $port = $row['port'] ?: 5000;
        $url = "http://{$row['ip_address']}:{$port}/api/system/health";
    }
} catch (Throwable $e) {}

$ctx = stream_context_create([
    'http' => [
        'timeout' => 3,
        'ignore_errors' => true,
    ]
]);

$json = @file_get_contents($url, false, $ctx);
if ($json === false) {
    http_response_code(503);
    echo json_encode(["error" => "Pi offline", "online" => false]);
    exit;
}

echo $json;
