<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#eff6ff', 100:'#dbeafe', 200:'#bfdbfe', 300:'#93c5fd', 400:'#60a5fa', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8', 800:'#1e3a5f', 900:'#0f172a' },
                        accent: { 400:'#f97316', 500:'#ea580c' },
                        danger: { 400:'#f87171', 500:'#ef4444', 600:'#dc2626' }
                    }
                }
            }
        }
    </script>
    <!-- Heroicons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
        .pulse-dot { animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
        .sidebar-link { transition: all 0.2s; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .stream-container { background: #000; border-radius: 12px; overflow: hidden; position: relative; }
        .stream-container img { width: 100%; height: auto; display: block; }
        .stat-card { position: relative; overflow: hidden; }
        .stat-card::before { content:''; position:absolute; top:-50%; right:-50%; width:100%; height:100%; background:radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%); }
    </style>
</head>
<body class="bg-brand-900 text-gray-100 min-h-screen">
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-brand-900 border-r border-white/10 flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">Bunny Door</h1>
                    <p class="text-xs text-gray-400">Access Control v<?= APP_VERSION ?></p>
                </div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <?php
            $currentPage = basename($_SERVER['PHP_SELF'], '.php');
            $menuItems = [
                ['icon' => 'fa-chart-pie', 'label' => 'Dashboard', 'href' => 'index.php', 'page' => 'index'],
                ['icon' => 'fa-video', 'label' => 'กล้องวงจรปิด', 'href' => 'cameras.php', 'page' => 'cameras'],
                ['icon' => 'fa-users', 'label' => 'พนักงาน', 'href' => 'employees.php', 'page' => 'employees'],
                ['icon' => 'fa-clock-rotate-left', 'label' => 'ประวัติเข้า-ออก', 'href' => 'logs.php', 'page' => 'logs'],
                ['icon' => 'fa-triangle-exclamation', 'label' => 'การแจ้งเตือน', 'href' => 'alerts.php', 'page' => 'alerts'],
                ['icon' => 'fa-gear', 'label' => 'ตั้งค่าระบบ', 'href' => 'settings.php', 'page' => 'settings'],
            ];
            foreach ($menuItems as $item):
                $active = ($currentPage === $item['page']) ? 'active bg-blue-500/15 text-blue-400' : 'text-gray-400';
            ?>
            <a href="<?= $item['href'] ?>" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $active ?>">
                <i class="fas <?= $item['icon'] ?> w-5 text-center"></i>
                <span class="font-medium"><?= $item['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="p-4 border-t border-white/10">
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span class="pulse-dot w-2 h-2 bg-green-400 rounded-full" id="serverStatus"></span>
                <span id="serverStatusText">System Online</span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 p-8">
