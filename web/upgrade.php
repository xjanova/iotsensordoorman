<?php
/**
 * Schema Upgrade — apply settings + columns ใหม่ที่เพิ่มเข้ามาภายหลัง
 * ใช้เมื่อ pull โค้ดใหม่แล้วต้องการ apply schema changes โดยไม่ต้องล้าง DB
 *
 * เปิด: /upgrade.php (ต้อง login)
 * กด "ตรวจ & อัปเกรด" → แสดง diff + apply
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// require login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php?next=upgrade.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$action = $_GET['action'] ?? 'check';
$messages = [];
$applied = false;

try {
    $db = getDB();
} catch (PDOException $e) {
    $messages[] = ['type' => 'error', 'text' => 'เชื่อมต่อ DB ไม่ได้: ' . $e->getMessage()];
    $db = null;
}

// Settings ที่ต้องมี (key => [default_value, description])
$requiredSettings = [
    'auto_pairing_enabled' => ['1', 'Zero-config: รับ pair อุปกรณ์ใน LAN เดียวกันโดยไม่ต้องใช้ token (1=เปิด)'],
    'db_share_enabled'     => ['1', 'อนุญาตให้ Pi ที่ TRUSTED ดึง DB credentials ผ่าน /api/pair/credentials.php'],
    'pairing_token'        => ['', 'Shared secret สำหรับ pair (auto-generated)'],
    'pairing_enabled'      => ['1', 'เปิดรับการ pair อุปกรณ์ใหม่'],
    'pi_unlock_mode'       => ['offline', 'Pi unlock policy: offline / online_only'],
];

$existing = [];
if ($db) {
    foreach ($db->query("SELECT setting_key, setting_value FROM settings") as $row) {
        $existing[$row['setting_key']] = $row['setting_value'];
    }
}

$missing = [];
foreach ($requiredSettings as $k => $v) {
    if (!array_key_exists($k, $existing)) {
        $missing[$k] = $v;
    }
}

// Check paired_devices table
$pairedDevicesExists = false;
if ($db) {
    try {
        $db->query("SELECT 1 FROM paired_devices LIMIT 1");
        $pairedDevicesExists = true;
    } catch (PDOException $e) {
        $pairedDevicesExists = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $token)) {
        $messages[] = ['type' => 'error', 'text' => 'CSRF token mismatch'];
    } else {
        // Apply missing settings
        foreach ($missing as $k => $v) {
            try {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
                $stmt->execute([$k, $v[0], $v[1]]);
                $messages[] = ['type' => 'ok', 'text' => "เพิ่ม setting: $k = '{$v[0]}'"];
            } catch (PDOException $e) {
                $messages[] = ['type' => 'error', 'text' => "Insert $k failed: " . $e->getMessage()];
            }
        }
        // Create paired_devices table ถ้ายังไม่มี
        if (!$pairedDevicesExists) {
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS paired_devices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    role ENUM('PI','ESP32','OTHER') NOT NULL,
                    device_id VARCHAR(64) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    hostname VARCHAR(100) DEFAULT NULL,
                    port INT DEFAULT NULL,
                    extra JSON DEFAULT NULL,
                    status ENUM('PENDING','TRUSTED','REVOKED') DEFAULT 'PENDING',
                    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                    approved_by INT DEFAULT NULL,
                    approved_at DATETIME DEFAULT NULL,
                    UNIQUE KEY uk_device (role, device_id),
                    INDEX idx_status (status),
                    INDEX idx_last_seen (last_seen)
                ) ENGINE=InnoDB COMMENT='Auto-pair devices'");
                $messages[] = ['type' => 'ok', 'text' => 'สร้างตาราง paired_devices'];
            } catch (PDOException $e) {
                $messages[] = ['type' => 'error', 'text' => 'Create table failed: ' . $e->getMessage()];
            }
        }
        $applied = true;
        $messages[] = ['type' => 'ok', 'text' => 'อัปเกรดเสร็จสมบูรณ์ — กลับไปหน้า Network'];
    }
}

// Re-read after apply
if ($applied && $db) {
    $existing = [];
    foreach ($db->query("SELECT setting_key, setting_value FROM settings") as $row) {
        $existing[$row['setting_key']] = $row['setting_value'];
    }
    $missing = [];
    foreach ($requiredSettings as $k => $v) {
        if (!array_key_exists($k, $existing)) $missing[$k] = $v;
    }
    try {
        $db->query("SELECT 1 FROM paired_devices LIMIT 1");
        $pairedDevicesExists = true;
    } catch (PDOException $e) {
        $pairedDevicesExists = false;
    }
}
?><!DOCTYPE html>
<html lang="th" data-theme="dark" data-accent="indigo">
<head>
    <meta charset="UTF-8">
    <title>Upgrade Schema - <?= APP_NAME ?></title>
    <script>
        (function() {
            try {
                var t = localStorage.getItem('doorman.theme') || 'dark';
                var a = localStorage.getItem('doorman.accent') || 'indigo';
                document.documentElement.setAttribute('data-theme', t);
                document.documentElement.setAttribute('data-accent', a);
            } catch(e) {}
        })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/doorman.css">
</head>
<body>

<div class="auth-bg">
    <div class="auth-card" style="max-width: 640px;">
        <div class="auth-brand">
            <div class="auth-mark">B</div>
            <div>
                <div style="font-size: 16px; font-weight: 600;">Schema Upgrade</div>
                <div class="tiny muted" style="margin-top: 2px;">apply settings ใหม่หลัง pull โค้ด</div>
            </div>
        </div>

        <?php foreach ($messages as $m): ?>
            <div class="auth-error" style="background: <?= $m['type']==='ok' ? 'color-mix(in oklch, var(--ok) 12%, var(--bg-2))' : 'color-mix(in oklch, var(--danger) 12%, var(--bg-2))' ?>; border-color: <?= $m['type']==='ok' ? 'var(--ok)' : 'var(--danger)' ?>;">
                <?= ico($m['type']==='ok' ? 'check' : 'alert-tri', 14) ?>
                <span><?= htmlspecialchars($m['text']) ?></span>
            </div>
        <?php endforeach; ?>

        <h2 style="margin-top: 16px; font-size: 15px;">สถานะ Schema</h2>

        <div style="display: grid; gap: 8px; margin-top: 10px;">
            <!-- paired_devices table -->
            <div style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-2);">
                <?php if ($pairedDevicesExists): ?>
                    <span style="color: var(--ok);"><?= ico('check', 16) ?></span>
                <?php else: ?>
                    <span style="color: var(--warn);"><?= ico('alert-tri', 16) ?></span>
                <?php endif; ?>
                <div style="flex: 1;">
                    <div style="font-size: 13px; font-weight: 600;">ตาราง <code class="mono">paired_devices</code></div>
                    <div class="tiny muted"><?= $pairedDevicesExists ? 'มีอยู่แล้ว' : 'ยังไม่มี — จะถูกสร้าง' ?></div>
                </div>
            </div>

            <!-- Settings -->
            <?php foreach ($requiredSettings as $k => $v): ?>
                <div style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-2);">
                    <?php if (array_key_exists($k, $existing)): ?>
                        <span style="color: var(--ok);"><?= ico('check', 16) ?></span>
                    <?php else: ?>
                        <span style="color: var(--warn);"><?= ico('alert-tri', 16) ?></span>
                    <?php endif; ?>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 13px; font-weight: 600;" class="mono"><?= htmlspecialchars($k) ?></div>
                        <div class="tiny muted"><?= htmlspecialchars($v[1]) ?></div>
                    </div>
                    <div class="mono tiny" style="color: var(--text-2);">
                        <?php if (array_key_exists($k, $existing)): ?>
                            = "<?= htmlspecialchars($existing[$k]) ?>"
                        <?php else: ?>
                            <span style="color: var(--warn);">ขาด — default "<?= htmlspecialchars($v[0]) ?>"</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($missing) || !$pairedDevicesExists): ?>
            <form method="POST" style="margin-top: 18px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" class="btn primary lg" style="width: 100%; justify-content: center;">
                    <?= ico('zap', 14) ?> Apply <?= count($missing) + (!$pairedDevicesExists ? 1 : 0) ?> changes
                </button>
            </form>
        <?php else: ?>
            <div style="margin-top: 18px; padding: 14px; background: color-mix(in oklch, var(--ok) 10%, var(--bg-2)); border: 1px solid var(--ok); border-radius: 8px; text-align: center;">
                <strong class="text-ok">✓ Schema ครบแล้ว — ไม่ต้องอัปเกรด</strong>
            </div>
        <?php endif; ?>

        <div style="margin-top: 14px; display: flex; gap: 8px; justify-content: center;">
            <a href="network.php" class="btn ghost sm">← กลับไป Network</a>
            <a href="index.php" class="btn ghost sm">← Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>
