<?php
/**
 * API: รับอัพโหลด snapshot จาก Pi (Face Server)
 * ใช้สำหรับเก็บรูปถ่ายขณะเข้า-ออก ที่เครื่อง Laragon แทน Pi
 *
 * POST /api/snapshot_upload.php
 *   - file: ไฟล์ภาพ (JPEG)
 *   - filename: ชื่อไฟล์ที่ต้องการ (e.g. cam1_somchai_20260315_120000.jpg)
 */
require_once __DIR__ . '/../config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// ตรวจสอบไฟล์
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'ไม่มีไฟล์หรืออัพโหลดผิดพลาด'], 400);
}

$file = $_FILES['file'];
$maxSize = 2 * 1024 * 1024; // 2MB

if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'ไฟล์ใหญ่เกินไป (สูงสุด 2MB)'], 400);
}

// ตรวจ MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mimeType !== 'image/jpeg' && $mimeType !== 'image/png') {
    jsonResponse(['error' => 'รองรับเฉพาะ JPEG/PNG'], 400);
}

// ชื่อไฟล์ (sanitize)
$requestedName = $_POST['filename'] ?? '';
$safeName = preg_replace('/[^A-Za-z0-9_\-.]/', '', basename($requestedName));
if (!$safeName || !preg_match('/\.(jpg|jpeg|png)$/i', $safeName)) {
    $safeName = 'snap_' . date('Ymd_His') . '_' . uniqid() . '.jpg';
}

// สร้างโฟลเดอร์ snapshots/ (แยกตามเดือน เพื่อไม่ให้ไฟล์เยอะเกิน)
$monthDir = date('Y-m');
$uploadDir = __DIR__ . '/../snapshots/' . $monthDir . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $safeName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'ไม่สามารถบันทึกไฟล์ได้'], 500);
}

// คืน path สำหรับเก็บใน DB
$relativePath = 'snapshots/' . $monthDir . '/' . $safeName;

jsonResponse([
    'success' => true,
    'path' => $relativePath,
    'filename' => $safeName,
]);
