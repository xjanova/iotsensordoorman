<?php
/**
 * Proxy: ถ่ายภาพจากกล้อง Pi + บันทึกไฟล์ (แก้ CORS)
 */
require_once __DIR__ . '/../config.php';

session_start();
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'กรุณาเข้าสู่ระบบ'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Preview: ดึง snapshot จากกล้อง
    $camera = preg_replace('/[^a-z]/', '', $_GET['camera'] ?? 'outside');
    $url = FACE_SERVER_URL . '/api/capture/photo?camera=' . $camera;

    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $img = @file_get_contents($url, false, $ctx);
    if ($img === false) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(["error" => "กล้องไม่พร้อม"]);
        exit;
    }

    header('Content-Type: image/jpeg');
    header('Cache-Control: no-cache');
    echo $img;
    exit;
}

if ($method === 'POST') {
    // Capture: ถ่ายภาพ + face detection + บันทึก
    $input = json_decode(file_get_contents('php://input'), true);
    $camera = preg_replace('/[^a-z]/', '', $input['camera'] ?? 'outside');
    $empCode = preg_replace('/[^A-Za-z0-9\-]/', '', $input['emp_code'] ?? 'capture');

    $url = FACE_SERVER_URL . '/api/capture/save';
    $postData = json_encode(['camera' => $camera, 'emp_code' => $empCode]);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData,
            'timeout' => 15,
        ]
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        jsonResponse(['error' => 'Face Server ไม่ตอบสนอง'], 503);
    }

    // ดึง HTTP status code
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/(\d{3})/', $statusLine, $m);
    $code = intval($m[1] ?? 200);

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo $response;
    exit;
}

jsonResponse(['error' => 'Method not allowed'], 405);
