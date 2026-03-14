<?php
/**
 * Debug: ตรวจสอบการเชื่อมต่อ DB และ .env
 * ลบไฟล์นี้หลังใช้งานเสร็จ
 */
echo "<h2>Bunny Door - Debug</h2>";
echo "<pre>";

// 1. Check .env file
$envFile = __DIR__ . '/.env';
echo "=== .env file ===\n";
if (file_exists($envFile)) {
    echo "Found: $envFile\n";
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $i => $line) {
        // Hide password partially
        if (stripos($line, 'PASS') !== false) {
            echo "Line $i: [HIDDEN]\n";
        } else {
            echo "Line $i: [$line]\n";
        }
    }
    echo "File size: " . filesize($envFile) . " bytes\n";
    // Check BOM
    $raw = file_get_contents($envFile);
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        echo "WARNING: File has BOM (byte order mark)! Remove it!\n";
    }
} else {
    echo "ERROR: .env NOT FOUND at $envFile\n";
}

echo "\n=== Loading config ===\n";
try {
    require_once __DIR__ . '/config.php';
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_PORT: " . DB_PORT . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "DB_PASS: " . (DB_PASS ? '[SET]' : '[EMPTY]') . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "FACE_SERVER_URL: " . FACE_SERVER_URL . "\n";
} catch (Exception $e) {
    echo "ERROR loading config: " . $e->getMessage() . "\n";
}

echo "\n=== DB Connection ===\n";
try {
    $db = getDB();
    echo "Connected OK!\n";

    $stmt = $db->query("SELECT COUNT(*) as c FROM employees");
    $count = $stmt->fetch()['c'];
    echo "Employees in DB: $count\n";

    $stmt = $db->query("SHOW TABLES");
    echo "Tables: ";
    $tables = [];
    while ($row = $stmt->fetch()) {
        $tables[] = array_values($row)[0];
    }
    echo implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
