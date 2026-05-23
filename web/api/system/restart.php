<?php
/**
 * POST /api/system/restart.php
 * Proxy: สั่ง Pi face_server restart ผ่าน /api/system/restart
 *
 * Auth: ต้อง login + CSRF
 */
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

requireLogin();
requireCsrf();

$url = FACE_SERVER_URL . '/api/system/restart';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '',
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($code !== 200 || !$resp) {
    jsonResponse([
        'success' => false,
        'error' => 'Pi ไม่ตอบ — ตรวจว่า Pi pair กับ web แล้วยัง',
        'detail' => $err ?: "HTTP $code",
        'face_server' => FACE_SERVER_URL,
    ], 503);
}

$data = json_decode($resp, true);
if (!is_array($data)) {
    jsonResponse(['success' => false, 'error' => 'ผลลัพธ์จาก Pi ไม่ใช่ JSON'], 502);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
