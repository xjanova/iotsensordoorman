<?php
/**
 * Bunny Door System - Configuration
 * ค่าตั้งระบบฝั่ง Web
 */

// Database
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', 'Theking222');
define('DB_NAME', 'bunny_door');

// Python Face Server
define('FACE_SERVER_URL', 'http://localhost:5000');

// Application
define('APP_NAME', 'Bunny Door System');
define('APP_VERSION', '2.0');
define('TIMEZONE', 'Asia/Bangkok');

date_default_timezone_set(TIMEZONE);

/**
 * Database connection (PDO)
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

/**
 * JSON Response helper
 */
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
