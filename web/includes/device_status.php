<?php
/**
 * Device Status Resolver — source of truth = paired_devices (Auto-Pair)
 * ============================================================================
 * ใช้ตรวจสถานะ Pi / ESP32 / camera ทุกที่ในระบบ
 * ลำดับการตัดสินใจ (เลือกอันแรกที่มี):
 *   1. paired_devices (TRUSTED + last_seen สด < $freshSec วินาที) — แม่นที่สุด
 *   2. system_status heartbeat (สดภายใน 60s) — fallback
 */

if (!function_exists('getDeviceStatus')) {

/**
 * คืน array ที่บอกสถานะของ Pi/ESP32 + camera
 *
 * @param int $freshSec อายุ paired_devices.last_seen สูงสุดที่ยังถือว่า online (default 30s)
 * @return array{pi_online:bool, pi_ip:string, esp32_online:bool, esp32_ip:string,
 *               cam_outside:bool, cam_inside:bool, raw_system_status:array, raw_paired:array}
 */
function getDeviceStatus(int $freshSec = 30): array {
    $result = [
        'pi_online'    => false,
        'pi_ip'        => '',
        'esp32_online' => false,
        'esp32_ip'     => '',
        'cam_outside'  => false,
        'cam_inside'   => false,
        'raw_system_status' => [],
        'raw_paired'        => [],
    ];

    try {
        $db = getDB();
    } catch (Throwable $e) {
        return $result;
    }

    // ── 1) paired_devices (source of truth) ──
    try {
        $rows = $db->query("SELECT role, ip_address, status,
                                   TIMESTAMPDIFF(SECOND, last_seen, NOW()) AS age_sec
                            FROM paired_devices
                            WHERE status = 'TRUSTED'
                            ORDER BY last_seen DESC")->fetchAll();
        foreach ($rows as $r) {
            $result['raw_paired'][] = $r;
            $fresh = $r['age_sec'] !== null && $r['age_sec'] <= $freshSec;
            if ($r['role'] === 'PI') {
                if (!$result['pi_ip']) $result['pi_ip'] = $r['ip_address'];
                if ($fresh) $result['pi_online'] = true;
            } elseif ($r['role'] === 'ESP32') {
                if (!$result['esp32_ip']) $result['esp32_ip'] = $r['ip_address'];
                if ($fresh) $result['esp32_online'] = true;
            }
        }
    } catch (Throwable $e) {}

    // ── 2) system_status (camera + fallback สำหรับ Pi/ESP32) ──
    try {
        $rows = $db->query("SELECT component, status, ip_address,
                                   TIMESTAMPDIFF(SECOND, last_heartbeat, NOW()) AS age_sec
                            FROM system_status")->fetchAll();
        foreach ($rows as $r) {
            $result['raw_system_status'][$r['component']] = $r;
            $fresh = $r['age_sec'] !== null && $r['age_sec'] <= 60;
            $isOnline = $fresh && $r['status'] === 'ONLINE';
            switch ($r['component']) {
                case 'camera_outside': $result['cam_outside'] = $isOnline; break;
                case 'camera_inside':  $result['cam_inside']  = $isOnline; break;
                case 'face_server':
                case 'raspberry_pi':
                    // ใช้ heartbeat ถ้า paired_devices ยังไม่ตอบรับ
                    if (!$result['pi_online'] && $isOnline) $result['pi_online'] = true;
                    if (!$result['pi_ip'] && $r['ip_address']) $result['pi_ip'] = $r['ip_address'];
                    break;
                case 'esp32':
                    if (!$result['esp32_online'] && $isOnline) $result['esp32_online'] = true;
                    if (!$result['esp32_ip'] && $r['ip_address']) $result['esp32_ip'] = $r['ip_address'];
                    break;
            }
        }
    } catch (Throwable $e) {}

    return $result;
}

}
