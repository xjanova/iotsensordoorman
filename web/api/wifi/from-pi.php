<?php
/**
 * GET /api/wifi/from-pi.php
 * Proxy: ดึง WiFi credentials ของ Pi ผ่าน face_server endpoint /api/wifi/current
 * (ต้อง login)
 */
require_once __DIR__ . '/../../includes/api_auth.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$url = FACE_SERVER_URL . '/api/wifi/current';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($code !== 200 || !$resp) {
    jsonResponse([
        'ok' => false,
        'error' => 'Pi ไม่ตอบ',
        'detail' => $err ?: "HTTP $code",
        'face_server' => FACE_SERVER_URL,
    ], 503);
}

$data = json_decode($resp, true);
if (!is_array($data)) {
    jsonResponse(['ok' => false, 'error' => 'ผลลัพธ์จาก Pi ไม่ใช่ JSON'], 502);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
