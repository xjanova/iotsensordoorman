<?php
/**
 * Login Page - เข้าสู่ระบบ
 */
require_once __DIR__ . '/config.php';

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
    // Check rate limit
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;

    if ($attempts >= $maxAttempts && (time() - $lastAttempt) < ($lockoutMinutes * 60)) {
        $remaining = ceil(($lockoutMinutes * 60 - (time() - $lastAttempt)) / 60);
        $error = "ล็อกชั่วคราว กรุณารอ {$remaining} นาที";
    } else {
        // Reset if lockout expired
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
                    // Success - regenerate session
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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: {
                brand: { 900:'#0f172a', 800:'#1e3a5f' },
                accent: { 400:'#f97316', 500:'#ea580c' }
            }}}
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }
    </style>
</head>
<body class="bg-brand-900 text-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="w-20 h-20 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/20">
            <i class="fas fa-shield-alt text-white text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold"><?= APP_NAME ?></h1>
        <p class="text-gray-400 text-sm mt-1">Access Control System</p>
    </div>

    <div class="glass rounded-2xl p-8">
        <h2 class="text-xl font-bold text-center mb-6">เข้าสู่ระบบ</h2>

        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3 mb-4">
            <p class="text-red-400 text-sm"><i class="fas fa-exclamation-circle mr-1"></i> <?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">ชื่อผู้ใช้</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-3 text-gray-500"></i>
                    <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required autofocus
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
                           placeholder="Username" autocomplete="username">
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">รหัสผ่าน</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-3 text-gray-500"></i>
                    <input type="password" name="password" required
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
                           placeholder="Password" autocomplete="current-password">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl transition font-medium">
                <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
            </button>
        </form>
    </div>

    <p class="text-center text-gray-600 text-xs mt-6"><?= APP_NAME ?> v<?= APP_VERSION ?> &copy; <?= date('Y') ?></p>
</div>

</body>
</html>
