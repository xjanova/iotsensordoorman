<?php
/**
 * API: อัพโหลดรูปภาพพนักงาน + ตรวจจับใบหน้าอัตโนมัติ
 * รองรับ: JPG, PNG, WEBP (สูงสุด 5MB)
 */
require_once __DIR__ . '/../config.php';

session_start();
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'กรุณาเข้าสู่ระบบ'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Validate file
if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'ไฟล์มีขนาดใหญ่เกินไป (เกินค่า php.ini)',
        UPLOAD_ERR_FORM_SIZE  => 'ไฟล์มีขนาดใหญ่เกินไป',
        UPLOAD_ERR_PARTIAL    => 'อัพโหลดไม่สมบูรณ์',
        UPLOAD_ERR_NO_FILE    => 'ไม่ได้เลือกไฟล์',
    ];
    $code = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    jsonResponse(['error' => $errors[$code] ?? 'เกิดข้อผิดพลาดในการอัพโหลด'], 400);
}

$file = $_FILES['photo'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)'], 400);
}

// Validate MIME type
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    jsonResponse(['error' => 'รองรับเฉพาะไฟล์ JPG, PNG, WEBP เท่านั้น'], 400);
}

// Generate safe filename
$empCode = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['emp_code'] ?? 'unknown');
$ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mimeType];
$filename = $empCode . '_' . time() . '.' . $ext;

// Create upload directory
$uploadDir = __DIR__ . '/../uploads/faces/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Move file
$destination = $uploadDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'ไม่สามารถบันทึกไฟล์ได้'], 500);
}

// Face validation via Python Face Server (optional - non-blocking)
$faceValidation = null;
$skipValidation = !empty($_POST['skip_face_check']);

if (!$skipValidation) {
    $faceServerUrl = FACE_SERVER_URL . '/api/face/validate';
    $curlFile = new CURLFile($destination, $mimeType, $filename);

    $ch = curl_init($faceServerUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['photo' => $curlFile],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $faceValidation = json_decode($response, true);
    }
}

jsonResponse([
    'success' => true,
    'filename' => $filename,
    'path' => 'uploads/faces/' . $filename,
    'size' => filesize($destination),
    'face_validation' => $faceValidation,
]);
