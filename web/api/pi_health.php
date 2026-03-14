<?php
/**
 * Proxy: ดึงข้อมูล Pi health จาก face_server (แก้ปัญหา CORS)
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$url = FACE_SERVER_URL . '/api/system/health';

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
