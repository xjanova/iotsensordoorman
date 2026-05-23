<?php
/**
 * Bunny Door System - Update API
 * เช็คเวอร์ชันและอัพเดทจาก GitHub
 */
require_once __DIR__ . '/../includes/api_auth.php';

requireLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// === Check current version ===
if ($action === 'check') {
    // อ่าน version.json ของเครื่อง
    $localVersionFile = realpath(__DIR__ . '/../../version.json');
    if (!$localVersionFile) $localVersionFile = realpath(__DIR__ . '/../../../version.json');
    $localVersion = $localVersionFile ? json_decode(file_get_contents($localVersionFile), true) : null;

    // ดึง version.json จาก GitHub
    $ghUrl = 'https://raw.githubusercontent.com/xjanova/iotsensordoorman/main/version.json';
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'user_agent' => 'BunnyDoor/1.0']]);
    $remoteJson = @file_get_contents($ghUrl, false, $ctx);
    $remoteVersion = $remoteJson ? json_decode($remoteJson, true) : null;

    $hasUpdate = false;
    if ($localVersion && $remoteVersion) {
        $hasUpdate = version_compare($remoteVersion['version'], $localVersion['version'], '>') ||
                     ($remoteVersion['version'] === $localVersion['version'] && ($remoteVersion['build'] ?? 0) > ($localVersion['build'] ?? 0));
    }

    jsonResponse([
        'success' => true,
        'local' => $localVersion,
        'remote' => $remoteVersion,
        'has_update' => $hasUpdate,
    ]);
}

// === Pull update from GitHub === (write op → require CSRF)
if ($action === 'pull') {
    requireCsrf();

    // หาตำแหน่ง git repo
    $repoDir = realpath(__DIR__ . '/../../');
    if (!is_dir($repoDir . '/.git')) {
        $repoDir = realpath(__DIR__ . '/../../../');
    }
    if (!is_dir($repoDir . '/.git')) {
        jsonResponse(['success' => false, 'error' => 'ไม่พบ Git repository'], 500);
    }

    // รัน git pull
    $output = [];
    $returnCode = 0;
    $cmd = 'cd ' . escapeshellarg($repoDir) . ' && git pull origin main 2>&1';
    exec($cmd, $output, $returnCode);

    $outputStr = implode("\n", $output);

    if ($returnCode === 0) {
        $newVersionFile = $repoDir . '/version.json';
        $newVersion = file_exists($newVersionFile) ? json_decode(file_get_contents($newVersionFile), true) : null;

        jsonResponse([
            'success' => true,
            'message' => 'อัพเดทสำเร็จ!',
            'output' => $outputStr,
            'new_version' => $newVersion,
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'error' => 'Git pull ล้มเหลว',
            'output' => $outputStr,
        ], 500);
    }
}

jsonResponse(['error' => 'Invalid action'], 400);
