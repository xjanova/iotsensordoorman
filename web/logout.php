<?php
/**
 * Logout - ออกจากระบบ
 * ต้องเป็น POST เพื่อกัน CSRF logout via <img src=logout.php>
 * GET → ตอบ form ยืนยัน
 */
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจ CSRF
    $given = $_POST['csrf_token'] ?? '';
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $given)) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
    header('Location: login.php');
    exit;
}

// GET: แสดง form ยืนยัน (auto-submit ผ่าน JS — UX เหมือนเดิม)
$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ออกจากระบบ...</title>
</head>
<body style="background:#0f1115; color:#e5e7eb; font-family:system-ui; display:grid; place-items:center; min-height:100vh; margin:0;">
<form method="POST" action="logout.php" id="logoutForm" style="text-align:center;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
    <p style="margin-bottom:14px;">กำลังออกจากระบบ...</p>
    <button type="submit" class="btn primary">ออกจากระบบ</button>
</form>
<script>document.getElementById('logoutForm').submit();</script>
</body>
</html>
