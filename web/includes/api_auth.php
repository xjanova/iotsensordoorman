<?php
/**
 * API Authentication Helper
 * ใช้ร่วมในไฟล์ api/*.php เพื่อมาตรฐานเดียวกัน
 * - ตั้ง session security settings เหมือนกัน
 * - มี requireLogin() / requirePairToken() ให้เรียกใช้
 */
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/**
 * ตรวจ session admin — ใช้สำหรับ endpoint ที่ admin เรียกผ่าน browser
 */
function requireLogin(): void {
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

/**
 * ดึง pairing token (shared secret ระหว่าง web/Pi/ESP32)
 * เก็บใน settings table key = 'pairing_token'
 */
function getPairingToken(): ?string {
    static $cached = null;
    if ($cached !== null) return $cached ?: null;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'pairing_token' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        $cached = $row && !empty($row['setting_value']) ? $row['setting_value'] : '';
        return $cached ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * ตรวจ X-Pair-Token header — ใช้สำหรับ device-to-device API (Pi/ESP32 → Web)
 * คืน true ถ้า token ตรงกับที่เก็บใน settings (เปรียบเทียบแบบ timing-safe)
 */
function requirePairToken(): void {
    $expected = getPairingToken();
    if (!$expected) {
        jsonResponse(['error' => 'Pairing not configured'], 503);
    }
    $given = $_SERVER['HTTP_X_PAIR_TOKEN'] ?? '';
    if ($given === '' || !hash_equals($expected, $given)) {
        jsonResponse(['error' => 'Invalid pair token'], 401);
    }
}

/**
 * ตรวจว่า login admin หรือมี pair token (ใช้กรณี endpoint ที่ทั้งคนและ device เรียกได้)
 */
function requireLoginOrPairToken(): void {
    if (!empty($_SESSION['admin_id'])) return;
    $expected = getPairingToken();
    $given = $_SERVER['HTTP_X_PAIR_TOKEN'] ?? '';
    if ($expected && $given !== '' && hash_equals($expected, $given)) return;
    jsonResponse(['error' => 'Unauthorized'], 401);
}

/**
 * ตรวจ CSRF token (สำหรับ POST จาก browser)
 * อ่านจาก header X-CSRF-Token หรือ form field csrf_token
 */
function requireCsrf(): void {
    $given = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || $given === '' || !hash_equals($_SESSION['csrf_token'], $given)) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
}
