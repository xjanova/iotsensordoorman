<?php
/**
 * First-time Setup - ตั้งค่าผู้ดูแลระบบครั้งแรก
 */
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin already exists
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as c FROM admin_users");
    if ($stmt->fetch()['c'] > 0) {
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    $dbError = true;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '') {
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
            $success = true;
        } catch (PDOException $e) {
            error_log("[Setup] " . $e->getMessage());
            $error = 'เกิดข้อผิดพลาดในการสร้างบัญชี';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - <?= APP_NAME ?></title>
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

<div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="w-20 h-20 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/20">
            <i class="fas fa-shield-alt text-white text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold"><?= APP_NAME ?></h1>
        <p class="text-gray-400 text-sm mt-1">Access Control System v<?= APP_VERSION ?></p>
    </div>

    <?php if (!empty($dbError)): ?>
    <div class="glass rounded-2xl p-8 text-center">
        <i class="fas fa-database text-red-400 text-4xl mb-4"></i>
        <h2 class="text-xl font-bold text-red-400 mb-2">ไม่สามารถเชื่อมต่อฐานข้อมูล</h2>
        <p class="text-gray-400 text-sm">กรุณาตรวจสอบการตั้งค่าฐานข้อมูลใน .env</p>
    </div>

    <?php elseif ($success): ?>
    <div class="glass rounded-2xl p-8 text-center">
        <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check text-green-400 text-3xl"></i>
        </div>
        <h2 class="text-xl font-bold text-green-400 mb-2">สร้างบัญชีสำเร็จ!</h2>
        <p class="text-gray-400 text-sm mb-6">คุณสามารถเข้าสู่ระบบด้วยบัญชีที่สร้างได้เลย</p>
        <a href="login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl transition font-medium">
            <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
        </a>
    </div>

    <?php else: ?>
    <div class="glass rounded-2xl p-8">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold">ตั้งค่าผู้ดูแลระบบ</h2>
            <p class="text-gray-400 text-sm mt-1">สร้างบัญชีผู้ดูแลระบบสำหรับเข้าใช้งานครั้งแรก</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3 mb-4">
            <p class="text-red-400 text-sm"><i class="fas fa-exclamation-circle mr-1"></i> <?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">ชื่อผู้ใช้ (Username)</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-3 text-gray-500"></i>
                    <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
                           placeholder="admin" autocomplete="username">
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">ชื่อที่แสดง</label>
                <div class="relative">
                    <i class="fas fa-id-badge absolute left-3 top-3 text-gray-500"></i>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($displayName ?? '') ?>"
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
                           placeholder="ผู้ดูแลระบบ" autocomplete="name">
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">รหัสผ่าน</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-3 text-gray-500"></i>
                    <input type="password" name="password" required minlength="6"
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
                           placeholder="อย่างน้อย 6 ตัวอักษร" autocomplete="new-password">
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">ยืนยันรหัสผ่าน</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-3 text-gray-500"></i>
                    <input type="password" name="confirm_password" required minlength="6"
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
                           placeholder="กรอกรหัสผ่านอีกครั้ง" autocomplete="new-password">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl transition font-medium mt-2">
                <i class="fas fa-user-plus mr-2"></i>สร้างบัญชีผู้ดูแลระบบ
            </button>
        </form>
    </div>
    <?php endif; ?>

    <p class="text-center text-gray-600 text-xs mt-6"><?= APP_NAME ?> v<?= APP_VERSION ?> &copy; <?= date('Y') ?></p>
</div>

</body>
</html>
