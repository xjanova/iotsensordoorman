<?php
/**
 * Bunny Door System - Configuration
 * ค่าตั้งระบบฝั่ง Web
 */

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

// Database (loaded from .env)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', intval(getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'bunny_door');

// Python Face Server — ใช้ getDB() singleton แทนสร้าง PDO ใหม่ (ลด connection ซ้ำซ้อน)
// ลำดับ: DB heartbeat (Pi ส่งมา) → .env FACE_SERVER_URL → fallback localhost
$_faceServerUrl = getenv('FACE_SERVER_URL') ?: 'http://localhost:5000';
try {
    $_piRow = getDB()
        ->query("SELECT ip_address, last_heartbeat FROM system_status WHERE component = 'face_server' AND status = 'ONLINE' LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);
    if ($_piRow && $_piRow['ip_address'] && (time() - strtotime($_piRow['last_heartbeat'])) < 120) {
        $_faceServerUrl = 'http://' . $_piRow['ip_address'] . ':5000';
    }
} catch (Throwable $e) {
    // เงียบไว้ — ใช้ fallback (DB ล่ม)
}
define('FACE_SERVER_URL', $_faceServerUrl);

// Application
define('APP_NAME', 'Bunny Door System');
define('TIMEZONE', 'Asia/Bangkok');

// Version from version.json
$_versionFile = realpath(__DIR__ . '/../version.json');
if (!$_versionFile) $_versionFile = realpath(__DIR__ . '/../../version.json');
$_versionData = $_versionFile ? json_decode(file_get_contents($_versionFile), true) : [];
define('APP_VERSION', $_versionData['version'] ?? '2.1.0');
define('APP_BUILD', $_versionData['build'] ?? 1);

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
 * CORS: echo origin กลับเฉพาะเมื่ออยู่ใน allowlist (.env CORS_ORIGINS) หรือ same-host
 */
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin) {
        $allowed = array_filter(array_map('trim', explode(',', getenv('CORS_ORIGINS') ?: '')));
        if (empty($allowed)) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $allowed = ["http://{$host}", "https://{$host}", 'http://localhost', 'http://127.0.0.1'];
        }
        if (in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Pair-Token');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
