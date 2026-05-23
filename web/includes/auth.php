<?php
/**
 * Authentication Middleware
 * ตรวจสอบสิทธิ์ผู้ใช้งานระบบ
 */
require_once __DIR__ . '/../config.php';

// Session security settings
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if any admin user exists
 */
function hasAdminUser(): bool {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT COUNT(*) as c FROM admin_users");
        return $stmt->fetch()['c'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

/**
 * Get current admin info
 */
function getCurrentAdmin(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? '',
        'display_name' => $_SESSION['admin_name'] ?? '',
    ];
}

/**
 * Generate CSRF token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrf(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get current page name
$currentFile = basename($_SERVER['PHP_SELF']);

// Skip auth check for login/setup pages
$publicPages = ['login.php', 'setup.php'];

// ── DEV bypass: default ON สำหรับช่วงพัฒนา (ไม่มีระบบ login)
//    ตั้ง DEV_BYPASS_LOGIN=0 ใน web/.env เพื่อปิด (production mode)
$_devBypass = getenv('DEV_BYPASS_LOGIN') !== '0';  // default = bypass
if ($_devBypass && !isLoggedIn() && !in_array($currentFile, $publicPages)) {
    try {
        $_db = getDB();
        // ถ้ายังไม่มี admin user → สร้าง dev admin อัตโนมัติ (ไม่ต้องไปหน้า setup)
        $_cnt = (int) $_db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($_cnt === 0) {
            $_hash = password_hash('dev', PASSWORD_BCRYPT);
            $_db->prepare("INSERT INTO admin_users (username, password_hash, display_name) VALUES (?, ?, ?)")
                ->execute(['dev', $_hash, 'Developer']);
        }
        $_row = $_db->query("SELECT id, username, display_name FROM admin_users ORDER BY id LIMIT 1")->fetch();
        if ($_row) {
            $_SESSION['admin_id'] = (int) $_row['id'];
            $_SESSION['admin_username'] = $_row['username'];
            $_SESSION['admin_name'] = $_row['display_name'] ?: $_row['username'];
        }
    } catch (Throwable $e) {
        // เงียบ — fallback ไป flow ปกติด้านล่าง
    }
}

if (!in_array($currentFile, $publicPages)) {
    // Check if admin exists
    if (!hasAdminUser()) {
        header('Location: setup.php');
        exit;
    }
    // Check if logged in
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Make current admin available to templates
$currentAdmin = getCurrentAdmin();
