<?php
/**
 * First-time Setup - ตั้งค่าผู้ดูแลระบบครั้งแรก
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/icons.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin already exists
$dbError = false;
$db = null;
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as c FROM admin_users");
    if ($stmt->fetch()['c'] > 0) {
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("[Setup] DB connect: " . $e->getMessage());
    $dbError = true;
}

$error = '';
$success = false;
$username = '';
$displayName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($dbError || !$db) {
        $error = 'เชื่อมต่อฐานข้อมูลไม่ได้';
    } elseif ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'ชื่อผู้ใช้ต้องมี 3-50 ตัวอักษร';
    } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
        $error = 'ชื่อผู้ใช้ต้องเป็นตัวอักษรภาษาอังกฤษ ตัวเลข หรือ _ เท่านั้น';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO admin_users (username, password_hash, display_name) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $displayName ?: $username]);

            // สร้าง pairing token อัตโนมัติเมื่อ setup ครั้งแรก
            $token = bin2hex(random_bytes(16));
            $tk = $db->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES ('pairing_token', ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $tk->execute([$token, 'Shared secret สำหรับ pair Pi/ESP32 กับ Web']);

            $success = true;
        } catch (PDOException $e) {
            error_log("[Setup] " . $e->getMessage());
            $error = 'เกิดข้อผิดพลาดในการสร้างบัญชี';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="dark" data-accent="indigo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - <?= APP_NAME ?></title>
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
    <div class="auth-card" style="max-width: 480px;">
        <div class="auth-brand">
            <div class="auth-mark">B</div>
            <div>
                <div style="font-size: 16px; font-weight: 600; letter-spacing: -0.01em;">Bunny Door</div>
                <div class="tiny muted" style="margin-top: 2px; font-family: var(--font-mono);"><?= APP_NAME ?> · v<?= APP_VERSION ?></div>
            </div>
        </div>

        <!-- Step indicator -->
        <div class="setup-steps">
            <div class="setup-step active">
                <div class="dot">1</div>
                <span class="lbl">บัญชีผู้ดูแล</span>
            </div>
            <div class="setup-line"></div>
            <div class="setup-step">
                <div class="dot">2</div>
                <span class="lbl">อุปกรณ์</span>
            </div>
            <div class="setup-line"></div>
            <div class="setup-step">
                <div class="dot">3</div>
                <span class="lbl">ทดสอบ</span>
            </div>
        </div>

        <?php if (!empty($dbError)): ?>
        <div class="setup-card" style="border-color: color-mix(in oklch, var(--danger) 30%, var(--border));">
            <div class="setup-icon danger"><?= ico('database', 22) ?></div>
            <div>
                <strong class="text-danger" style="font-size: 14px;">เชื่อมต่อฐานข้อมูลไม่ได้</strong>
                <p class="tiny muted" style="margin: 4px 0 0;">ตรวจสอบการตั้งค่าฐานข้อมูลใน <code class="mono">.env</code></p>
            </div>
        </div>

        <?php elseif ($success): ?>
        <div style="text-align: center; padding: 20px 0;">
            <div class="setup-icon ok" style="width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 16px;">
                <?= ico('check', 32) ?>
            </div>
            <h2 class="auth-title text-ok" style="margin-bottom: 4px;">สร้างบัญชีสำเร็จ!</h2>
            <p class="auth-sub">เริ่มใช้งานระบบได้ทันที</p>
            <a href="login.php" class="btn primary lg" style="margin-top: 14px; display: inline-flex;">
                <?= ico('log-in', 14) ?> เข้าสู่ระบบ
            </a>
        </div>

        <?php else: ?>
        <h1 class="auth-title">ตั้งค่าผู้ดูแลระบบ</h1>
        <p class="auth-sub">สร้างบัญชีผู้ดูแลระบบสำหรับเข้าใช้งานครั้งแรก</p>

        <?php if ($error): ?>
        <div class="auth-error">
            <?= ico('alert-tri', 14) ?>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label>
                <span>ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></span>
                <div class="auth-field">
                    <?= ico('users', 14) ?>
                    <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required class="input" placeholder="admin" autocomplete="username">
                </div>
            </label>
            <label>
                <span>ชื่อที่แสดง</span>
                <div class="auth-field">
                    <?= ico('id', 14) ?>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($displayName ?? '') ?>" class="input" placeholder="ผู้ดูแลระบบ" autocomplete="name">
                </div>
            </label>
            <label>
                <span>รหัสผ่าน <span class="text-danger">*</span></span>
                <div class="auth-field">
                    <?= ico('lock', 14) ?>
                    <input type="password" name="password" required minlength="6" class="input" placeholder="อย่างน้อย 6 ตัวอักษร" autocomplete="new-password">
                </div>
            </label>
            <label>
                <span>ยืนยันรหัสผ่าน <span class="text-danger">*</span></span>
                <div class="auth-field">
                    <?= ico('lock', 14) ?>
                    <input type="password" name="confirm_password" required minlength="6" class="input" placeholder="กรอกรหัสผ่านอีกครั้ง" autocomplete="new-password">
                </div>
            </label>
            <button type="submit" class="btn primary lg" style="justify-content: center; margin-top: 6px;">
                <?= ico('user-check', 14) ?> สร้างบัญชีผู้ดูแลระบบ
            </button>
        </form>

        <div class="auth-help">
            <?= ico('info', 14) ?>
            <span>หลังสร้างบัญชีแล้ว ระบบจะให้คุณเข้าสู่ระบบครั้งแรก</span>
        </div>
        <?php endif; ?>
    </div>

    <p class="auth-foot"><?= APP_NAME ?> v<?= APP_VERSION ?> &copy; <?= date('Y') ?></p>
</div>

</body>
</html>
