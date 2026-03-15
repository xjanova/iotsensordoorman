<?php
/**
 * Network Configuration API
 * จัดการ IP เครือข่ายของระบบ Bunny Door
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Require login for all operations
session_start();
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // ============================================================
    // Detect current network configuration
    // ============================================================
    case 'detect':
        // Laragon/Web server IP
        $serverIPs = [];
        $hostname = gethostname();

        // Get all IPs of this machine
        $hostIPs = gethostbynamel($hostname);
        if ($hostIPs) {
            foreach ($hostIPs as $ip) {
                if (strpos($ip, '127.') !== 0) {
                    $serverIPs[] = $ip;
                }
            }
        }

        // Fallback: use SERVER_ADDR
        if (empty($serverIPs) && !empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            $serverIPs[] = $_SERVER['SERVER_ADDR'];
        }

        // Current Pi IP from .env
        $piUrl = FACE_SERVER_URL;
        $piIP = '';
        if (preg_match('/http:\/\/([^:\/]+)/', $piUrl, $m)) {
            $piIP = $m[1];
        }

        // Current ESP32 IP from DB settings
        $db = getDB();
        $esp32IP = '';
        $wifiSSID = '';
        $serverUrl = '';
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('esp32_ip','wifi_ssid','server_url')");
            foreach ($stmt->fetchAll() as $row) {
                if ($row['setting_key'] === 'esp32_ip') $esp32IP = $row['setting_value'];
                if ($row['setting_key'] === 'wifi_ssid') $wifiSSID = $row['setting_value'];
                if ($row['setting_key'] === 'server_url') $serverUrl = $row['setting_value'];
            }
        } catch (Exception $e) {}

        // Test Pi connection
        $piOnline = false;
        if ($piIP && $piIP !== 'localhost') {
            $ch = curl_init("http://{$piIP}:5000/api/system/health");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $piOnline = ($httpCode === 200);
        }

        jsonResponse([
            'success' => true,
            'web_server' => [
                'hostname' => $hostname,
                'ips' => $serverIPs,
                'primary_ip' => $serverIPs[0] ?? 'unknown',
            ],
            'raspberry_pi' => [
                'ip' => $piIP,
                'url' => $piUrl,
                'online' => $piOnline,
            ],
            'esp32' => [
                'ip' => $esp32IP,
                'wifi_ssid' => $wifiSSID,
                'server_url' => $serverUrl,
            ],
        ]);
        break;

    // ============================================================
    // Save web .env (FACE_SERVER_URL)
    // ============================================================
    case 'save_web_env':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'POST required'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $piIP = trim($input['pi_ip'] ?? '');

        if (empty($piIP)) {
            jsonResponse(['error' => 'กรุณาระบุ IP ของ Raspberry Pi'], 400);
        }

        // Validate IP format
        if (!filter_var($piIP, FILTER_VALIDATE_IP)) {
            jsonResponse(['error' => 'รูปแบบ IP ไม่ถูกต้อง'], 400);
        }

        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            jsonResponse(['error' => '.env file not found'], 500);
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        $newLines = [];
        $found = false;
        foreach ($lines as $line) {
            if (strpos($line, 'FACE_SERVER_URL=') === 0) {
                $newLines[] = "FACE_SERVER_URL=http://{$piIP}:5000";
                $found = true;
            } else {
                $newLines[] = $line;
            }
        }
        if (!$found) {
            $newLines[] = "FACE_SERVER_URL=http://{$piIP}:5000";
        }

        file_put_contents($envFile, implode("\n", $newLines) . "\n");
        jsonResponse(['success' => true, 'message' => "อัพเดท FACE_SERVER_URL เป็น http://{$piIP}:5000"]);
        break;

    // ============================================================
    // Update Pi .env via face_server API (if available)
    // ============================================================
    case 'save_pi_env':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'POST required'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $piIP = trim($input['pi_ip'] ?? '');
        $dbHost = trim($input['db_host'] ?? '');

        if (empty($piIP) || empty($dbHost)) {
            jsonResponse(['error' => 'กรุณาระบุ IP ทั้งหมด'], 400);
        }

        // We can't directly edit Pi's .env, so provide the command
        jsonResponse([
            'success' => true,
            'message' => 'ใช้คำสั่งด้านล่างบน Pi',
            'command' => "ssh root1@{$piIP} \"sed -i 's/DB_HOST=.*/DB_HOST={$dbHost}/' ~/bunny-door/python/.env && cd ~/bunny-door/python && pkill -f face_server.py; sleep 1; nohup python3 face_server.py > /tmp/bunny.log 2>&1 &\"",
        ]);
        break;

    // ============================================================
    // Update ESP32 settings in DB
    // ============================================================
    case 'save_esp32':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'POST required'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $serverUrl = trim($input['server_url'] ?? '');
        $esp32Ip = trim($input['esp32_ip'] ?? '');
        $wifiSsid = trim($input['wifi_ssid'] ?? '');

        $db = getDB();
        $upsert = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

        if ($serverUrl) {
            $upsert->execute(['server_url', $serverUrl, $serverUrl]);
        }
        if ($esp32Ip) {
            $upsert->execute(['esp32_ip', $esp32Ip, $esp32Ip]);

            // แจ้ง Pi ให้อัพเดท ESP32 IP ด้วย
            $piUrl = FACE_SERVER_URL;
            $ch = curl_init("{$piUrl}/api/config/esp32");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['ip' => $esp32Ip]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
        if ($wifiSsid) {
            $upsert->execute(['wifi_ssid', $wifiSsid, $wifiSsid]);
        }

        jsonResponse(['success' => true, 'message' => "อัพเดทการตั้งค่า ESP32 สำเร็จ"]);
        break;

    // ============================================================
    // Test connection to a specific IP:port
    // ============================================================
    case 'test':
        $ip = $_GET['ip'] ?? '';
        $port = intval($_GET['port'] ?? 5000);
        $type = $_GET['type'] ?? 'pi';

        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            jsonResponse(['error' => 'IP ไม่ถูกต้อง'], 400);
        }

        if ($type === 'pi') {
            $ch = curl_init("http://{$ip}:{$port}/api/system/health");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($result, true);
                jsonResponse([
                    'success' => true,
                    'online' => true,
                    'message' => "Pi ออนไลน์! CPU: {$data['cpu_percent']}%, RAM: {$data['ram_percent']}%",
                    'data' => $data,
                ]);
            } else {
                jsonResponse([
                    'success' => true,
                    'online' => false,
                    'message' => "ไม่สามารถเชื่อมต่อ Pi ที่ {$ip}:{$port}" . ($error ? " ({$error})" : ''),
                ]);
            }
        } elseif ($type === 'esp32') {
            $ch = curl_init("http://{$ip}/ping");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            jsonResponse([
                'success' => true,
                'online' => ($httpCode === 200 && trim($result) === 'pong'),
                'message' => ($httpCode === 200) ? "ESP32 ออนไลน์!" : "ไม่สามารถเชื่อมต่อ ESP32 ที่ {$ip}",
            ]);
        }
        break;

    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
