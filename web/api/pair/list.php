<?php
/**
 * Pair: list devices (admin UI)
 */
require_once __DIR__ . '/../../includes/api_auth.php';

requireLogin();

try {
    $db = getDB();
    $rows = $db->query("SELECT id, role, device_id, ip_address, hostname, port, extra, status,
                               last_seen, first_seen, approved_at
                        FROM paired_devices
                        ORDER BY status = 'PENDING' DESC, last_seen DESC")->fetchAll();

    // คำนวณ online/offline จาก last_seen (>30s = offline)
    $now = time();
    foreach ($rows as &$r) {
        $r['extra'] = $r['extra'] ? json_decode($r['extra'], true) : null;
        $age = $now - strtotime($r['last_seen']);
        $r['online'] = $age <= 60;
        $r['seconds_ago'] = $age;
    }

    jsonResponse(['devices' => $rows]);
} catch (PDOException $e) {
    error_log("[API pair/list] " . $e->getMessage());
    jsonResponse(['error' => 'DB error'], 500);
}
