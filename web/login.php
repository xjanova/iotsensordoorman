<?php
/**
 * Login Page - เข้าสู่ระบบ
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If no admin exists, redirect to setup
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as c FROM admin_users");
    if (intval($stmt->fetch()['c']) === 0) {
        header('Location: setup.php');
        exit;
    }
} catch (PDOException $e) {
    $dbError = true;
}

// Already logged in
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Rate limiting
$maxAttempts = 5;
$lockoutMinutes = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;

    if ($attempts >= $maxAttempts && (time() - $lastAttempt) < ($lockoutMinutes * 60)) {
        $remaining = ceil(($lockoutMinutes * 60 - (time() - $lastAttempt)) / 60);
        $error = "ล็อกชั่วคราว กรุณารอ {$remaining} นาที";
    } else {
        if ((time() - $lastAttempt) >= ($lockoutMinutes * 60)) {
            $_SESSION['login_attempts'] = 0;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
        } else {
            try {
                $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['display_name'] ?: $admin['username'];
                    $_SESSION['login_attempts'] = 0;
                    header('Location: index.php');
                    exit;
                } else {
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                    $_SESSION['last_login_attempt'] = time();
                    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                }
            } catch (PDOException $e) {
                error_log("[Login] " . $e->getMessage());
                $error = 'เกิดข้อผิดพลาดของฐานข้อมูล';
            }
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
            <label>
                <span>ชื่อผู้ใช้</span>
                <div class="auth-field">
                    <?= ico('users', 14) ?>
                    <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required autofocus class="input" placeholder="Username" autocomplete="username">
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
