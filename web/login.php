<?php
/**
 * Login Page - เข้าสู่ระบบ
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

// Session security (เหมือน auth.php)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// If no admin exists, redirect to setup
$dbError = false;
$db = null;
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as c FROM admin_users");
    if (intval($stmt->fetch()['c']) === 0) {
        header('Location: setup.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("[Login] DB connect: " . $e->getMessage());
    $dbError = true;
}

// Already logged in
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

// Rate limit config — DB-based (ไม่ใช่ session ซึ่งล้าง cookie แล้ว bypass ได้)
$maxAttempts = 5;
$lockoutMinutes = 15;
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

function countRecentFailures(PDO $db, string $ip, ?string $username, int $minutes): int {
    $sql = "SELECT COUNT(*) FROM login_attempts
            WHERE success = 0
              AND attempted_at >= (NOW() - INTERVAL ? MINUTE)
              AND (ip_address = ?" . ($username ? " OR username = ?" : "") . ")";
    $stmt = $db->prepare($sql);
    $params = [$minutes, $ip];
    if ($username) $params[] = $username;
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function logAttempt(PDO $db, string $ip, ?string $username, bool $success): void {
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $username, $success ? 1 : 0]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    // CSRF check
    if (!hash_equals($csrfToken, $token)) {
        $error = 'Session หมดอายุ กรุณาโหลดหน้าใหม่';
    } elseif ($dbError || !$db) {
        $error = 'เชื่อมต่อฐานข้อมูลไม่ได้ — ตรวจสอบ .env';
    } elseif ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            // Rate limit: นับ failure ใน {lockoutMinutes} นาทีล่าสุด ทั้ง IP และ username
            $failures = countRecentFailures($db, $clientIp, $username, $lockoutMinutes);
            if ($failures >= $maxAttempts) {
                $error = "ล็อกชั่วคราว — ลองใหม่ใน {$lockoutMinutes} นาที";
                logAttempt($db, $clientIp, $username, false);
            } else {
                $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    logAttempt($db, $clientIp, $username, true);
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['display_name'] ?: $admin['username'];
                    // CSRF token ใหม่หลัง login (ป้องกัน session fixation)
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: index.php');
                    exit;
                } else {
                    logAttempt($db, $clientIp, $username, false);
                    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                }
            }

            // เก็บข้อมูลแค่ 30 วันล่าสุด (housekeeping เบาๆ — sample 1/100)
            if (mt_rand(1, 100) === 1) {
                $db->exec("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 30 DAY");
            }
        } catch (PDOException $e) {
            error_log("[Login] " . $e->getMessage());
            $error = 'เกิดข้อผิดพลาดของฐานข้อมูล';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="dark" data-accent="indigo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - <?= APP_NAME ?></title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/doorman.css">
</head>
<body>

<div class="auth-bg">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-mark">B</div>
            <div>
                <div style="font-size: 16px; font-weight: 600; letter-spacing: -0.01em;">Bunny Door</div>
                <div class="tiny muted" style="margin-top: 2px; font-family: var(--font-mono);"><?= APP_NAME ?> · Access Control</div>
            </div>
        </div>
        <h1 class="auth-title">เข้าสู่ระบบ</h1>
        <p class="auth-sub">กรอกข้อมูลผู้ดูแลระบบเพื่อเข้าใช้งาน</p>

        <?php if ($error): ?>
        <div class="auth-error">
            <?= ico('alert-tri', 14) ?>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label>
                <span>ชื่อผู้ใช้</span>
                <div class="auth-field">
                    <?= ico('users', 14) ?>
                    <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required autofocus class="input" placeholder="Username" autocomplete="username">
                </div>
            </label>
            <label>
                <span>รหัสผ่าน</span>
                <div class="auth-field">
                    <?= ico('lock', 14) ?>
                    <input type="password" name="password" required class="input" placeholder="Password" autocomplete="current-password">
                </div>
            </label>
            <button type="submit" class="btn primary lg" style="justify-content: center; margin-top: 6px;">
                <?= ico('log-in', 14) ?> เข้าสู่ระบบ
            </button>
        </form>

        <div class="auth-help">
            <?= ico('alert-tri', 14) ?>
            <span>ผิด <?= $maxAttempts ?> ครั้ง ล็อกชั่วคราว <?= $lockoutMinutes ?> นาที</span>
        </div>
    </div>

    <p class="auth-foot"><?= APP_NAME ?> v<?= APP_VERSION ?> &copy; <?= date('Y') ?></p>
</div>

</body>
</html>
