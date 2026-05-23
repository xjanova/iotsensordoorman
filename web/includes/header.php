<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

// คำนวณตัวเลขใน sidebar (จำนวนพนักงาน, alert ค้าง) — ดึงเบาๆ
$_navCounts = ['employees' => null, 'alerts' => null];
try {
    $_db = getDB();
    $_navCounts['employees'] = (int) $_db->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $_navCounts['alerts']    = (int) $_db->query("SELECT COUNT(*) FROM anomaly_alerts WHERE is_resolved = 0")->fetchColumn();
} catch (Throwable $e) {
    // เงียบไว้ — sidebar ยังใช้งานได้แม้ DB ขัดข้อง
}

$_currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Crumb label per page
$_crumbLabels = [
    'index'     => 'ภาพรวมระบบ',
    'cameras'   => 'กล้องสด',
    'logs'      => 'ประวัติเข้า-ออก',
    'employees' => 'พนักงาน',
    'alerts'    => 'การแจ้งเตือน',
    'network'   => 'ตั้งค่าเครือข่าย',
    'settings'  => 'ตั้งค่าระบบ',
    'guide'     => 'คู่มือระบบ',
];
$_crumb = $_crumbLabels[$_currentPage] ?? ($pageTitle ?? '');

$_initials = $currentAdmin
    ? mb_strtoupper(mb_substr($currentAdmin['display_name'] ?: $currentAdmin['username'], 0, 2))
    : 'XM';
?>
<!DOCTYPE html>
<html lang="th" data-theme="dark" data-accent="indigo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>

    <!-- Avoid flash of wrong theme: apply persisted theme/accent BEFORE body renders -->
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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Doorman design tokens -->
    <link rel="stylesheet" href="assets/css/doorman.css">

    <style>
        /* Per-accent swatch override for accent menu */
        .accent-menu .opt[data-accent="emerald"] .sw { --swatch: #10b981; }
        .accent-menu .opt[data-accent="indigo"]  .sw { --swatch: #6366f1; }
        .accent-menu .opt[data-accent="blue"]    .sw { --swatch: #2563eb; }
        .accent-menu .opt[data-accent="amber"]   .sw { --swatch: #d97706; }

        /* Legacy class fallbacks — for any page still using them */
        .pulse-dot { animation: pulse-fade 2s infinite; }
        @keyframes pulse-fade { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
    </style>
</head>
<body>
<div class="app">

<!-- ============================================================ -->
<!-- Sidebar -->
<!-- ============================================================ -->
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-head">
        <div class="brand-mark">B</div>
        <div class="brand-name" title="<?= htmlspecialchars(APP_NAME) ?>">Bunny Door</div>
        <span class="brand-env" title="build <?= APP_BUILD ?>">v<?= APP_VERSION ?></span>
    </div>

    <nav>
        <div class="nav-section">Operations</div>
        <?php
        $_navOps = [
            ['icon' => 'home',   'label' => 'ภาพรวม',     'href' => 'index.php',     'page' => 'index'],
            ['icon' => 'camera', 'label' => 'กล้องสด',     'href' => 'cameras.php',   'page' => 'cameras'],
            ['icon' => 'list',   'label' => 'ประวัติเข้า-ออก', 'href' => 'logs.php',     'page' => 'logs'],
            ['icon' => 'users',  'label' => 'พนักงาน',     'href' => 'employees.php', 'page' => 'employees', 'count' => $_navCounts['employees']],
            ['icon' => 'bell',   'label' => 'การแจ้งเตือน', 'href' => 'alerts.php',    'page' => 'alerts',    'count' => $_navCounts['alerts'], 'danger' => true],
        ];
        foreach ($_navOps as $item): ?>
            <a href="<?= $item['href'] ?>" class="nav-item <?= $_currentPage === $item['page'] ? 'active' : '' ?>">
                <?= ico($item['icon'], 16) ?>
                <span><?= htmlspecialchars($item['label']) ?></span>
                <?php if (isset($item['count']) && $item['count'] !== null && $item['count'] > 0): ?>
                    <span class="count <?= !empty($item['danger']) ? 'danger' : '' ?>"><?= $item['count'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <div class="nav-section">System</div>
        <?php
        $_navSys = [
            ['icon' => 'network',  'label' => 'เครือข่าย',  'href' => 'network.php',  'page' => 'network'],
            ['icon' => 'settings', 'label' => 'ตั้งค่าระบบ', 'href' => 'settings.php', 'page' => 'settings'],
            ['icon' => 'book',     'label' => 'คู่มือระบบ',  'href' => 'guide.php',    'page' => 'guide'],
        ];
        foreach ($_navSys as $item): ?>
            <a href="<?= $item['href'] ?>" class="nav-item <?= $_currentPage === $item['page'] ? 'active' : '' ?>">
                <?= ico($item['icon'], 16) ?>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
        <div class="avatar"><?= htmlspecialchars($_initials) ?></div>
        <div class="who">
            <span class="name"><?= htmlspecialchars($currentAdmin['display_name'] ?? $currentAdmin['username'] ?? 'Admin') ?></span>
            <span class="role">Administrator</span>
        </div>
        <a href="logout.php" class="icon-btn danger" title="ออกจากระบบ" aria-label="ออกจากระบบ">
            <?= ico('power', 14) ?>
        </a>
    </div>
</aside>

<!-- ============================================================ -->
<!-- Main + Topbar -->
<!-- ============================================================ -->
<main>
    <div class="topbar">
        <div class="crumbs">
            <strong>Bunny Door</strong>
            <span class="sep">/</span>
            <span><?= htmlspecialchars($_crumb) ?></span>
        </div>

        <div class="topbar-right">
            <div class="clock">
                <span class="date" id="topClockDate">—</span>
                <span id="topClockTime">—</span>
            </div>

            <!-- Theme switcher -->
            <div class="theme-switch" role="tablist" aria-label="Theme">
                <button type="button" id="themeLight" title="Light theme" aria-label="Light theme"><?= ico('sun', 14) ?></button>
                <button type="button" id="themeDark"  title="Dark theme"  aria-label="Dark theme"><?= ico('moon', 14) ?></button>
            </div>

            <!-- Accent picker -->
            <div class="accent-picker">
                <button type="button" class="accent-trigger" id="accentTrigger" aria-haspopup="true" aria-expanded="false">
                    <span class="accent-swatch"></span>
                    <span id="accentLabel">Indigo</span>
                </button>
                <div class="accent-menu" id="accentMenu" role="menu">
                    <div class="opt" data-accent="emerald" role="menuitem"><span class="sw"></span><span>Emerald</span></div>
                    <div class="opt" data-accent="indigo"  role="menuitem"><span class="sw"></span><span>Indigo</span></div>
                    <div class="opt" data-accent="blue"    role="menuitem"><span class="sw"></span><span>Blue</span></div>
                    <div class="opt" data-accent="amber"   role="menuitem"><span class="sw"></span><span>Amber</span></div>
                </div>
            </div>

            <!-- Door state pill (เชื่อมกับ ESP32 health) -->
            <div class="door-pill locked" id="topDoorPill" title="สถานะประตู">
                <span class="led"></span>
                <span id="topDoorLabel">ประตูล็อก</span>
            </div>

            <!-- Primary action: Unlock door -->
            <button type="button" class="btn primary sm" id="topUnlockBtn" title="ปลดล็อกประตู">
                <?= ico('unlock', 14) ?>
                <span>ปลดล็อกประตู</span>
            </button>

            <!-- Logout shortcut -->
            <a href="logout.php" class="icon-btn" title="ออกจากระบบ" aria-label="ออกจากระบบ">
                <?= ico('power', 14) ?>
            </a>
        </div>
    </div>

    <div class="page">
