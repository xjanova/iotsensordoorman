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
