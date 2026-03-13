<?php
/**
 * API: สถิติสำหรับ Dashboard
 */
require_once __DIR__ . '/../config.php';

try {
    $db = getDB();

    $stats = [];

    // จำนวนพนักงานทั้งหมด
    $stmt = $db->query("SELECT COUNT(*) as total FROM employees");
    $stats['total_employees'] = $stmt->fetch()['total'];

    // เข้าวันนี้
    $stmt = $db->query("SELECT COUNT(DISTINCT employee_id) as c FROM access_logs WHERE direction='IN' AND DATE(created_at) = CURDATE() AND is_authorized = 1");
    $stats['today_in'] = $stmt->fetch()['c'];

    // ออกวันนี้
    $stmt = $db->query("SELECT COUNT(DISTINCT employee_id) as c FROM access_logs WHERE direction='OUT' AND DATE(created_at) = CURDATE() AND is_authorized = 1");
    $stats['today_out'] = $stmt->fetch()['c'];

    $stats['currently_inside'] = max(0, $stats['today_in'] - $stats['today_out']);

    // แจ้งเตือน
    $stmt = $db->query("SELECT COUNT(*) as c FROM anomaly_alerts WHERE is_resolved = 0");
    $stats['unresolved_alerts'] = $stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM anomaly_alerts WHERE DATE(created_at) = CURDATE()");
    $stats['today_alerts'] = $stmt->fetch()['c'];

    // System status
    $stmt = $db->query("SELECT component, status, last_heartbeat FROM system_status");
    $stats['system'] = $stmt->fetchAll();

    jsonResponse($stats);
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
