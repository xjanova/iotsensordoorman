<?php
/**
 * Bunny Door System - Update API
 * เช็คเวอร์ชันและอัพเดทจาก GitHub
 */
require_once __DIR__ . '/../config.php';
session_start();
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json; charset=utf-8');

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

    echo json_encode([
        'success' => true,
        'local' => $localVersion,
        'remote' => $remoteVersion,
        'has_update' => $hasUpdate,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// === Pull update from GitHub ===
if ($action === 'pull') {
    // หาตำแหน่ง git repo
    $repoDir = realpath(__DIR__ . '/../../');
    if (!is_dir($repoDir . '/.git')) {
        $repoDir = realpath(__DIR__ . '/../../../');
    }
    if (!is_dir($repoDir . '/.git')) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบ Git repository'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // รัน git pull
    $output = [];
    $returnCode = 0;
    $cmd = 'cd ' . escapeshellarg($repoDir) . ' && git pull origin main 2>&1';
    exec($cmd, $output, $returnCode);

    $outputStr = implode("\n", $output);

    if ($returnCode === 0) {
        // อ่าน version ใหม่
        $newVersionFile = $repoDir . '/version.json';
        $newVersion = file_exists($newVersionFile) ? json_decode(file_get_contents($newVersionFile), true) : null;

        echo json_encode([
            'success' => true,
            'message' => 'อัพเดทสำเร็จ!',
            'output' => $outputStr,
            'new_version' => $newVersion,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Git pull ล้มเหลว',
            'output' => $outputStr,
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
