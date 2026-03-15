<?php
/**
 * API: การแจ้งเตือนความผิดปกติ
 */
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') jsonResponse(['ok' => true]);

// Require login for write operations
if ($method === 'POST') {
    session_start();
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['error' => 'กรุณาเข้าสู่ระบบ'], 401);
    }
}

try {
    $db = getDB();

    switch ($method) {
        case 'GET':
            $limit = min(max(intval($_GET['limit'] ?? 20), 1), 500);
            $page = max(intval($_GET['page'] ?? 1), 1);
            $offset = ($page - 1) * $limit;
            $resolved = $_GET['resolved'] ?? null;
            $type = $_GET['type'] ?? null;
            $severity = $_GET['severity'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $search = $_GET['search'] ?? null;

            $where = [];
            $params = [];

            if ($resolved !== null && $resolved !== '') {
                $where[] = "is_resolved = ?";
                $params[] = intval($resolved);
            }

            if ($type && $type !== 'all') {
                $where[] = "alert_type = ?";
                $params[] = $type;
            }

            if ($severity && $severity !== 'all') {
                $where[] = "severity = ?";
                $params[] = $severity;
            }

            if ($dateFrom) {
                $where[] = "created_at >= ?";
                $params[] = $dateFrom . ' 00:00:00';
            }

            if ($dateTo) {
                $where[] = "created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }

            if ($search) {
                $where[] = "description LIKE ?";
                $params[] = '%' . $search . '%';
            }

            $whereSQL = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            // Count total
            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM anomaly_alerts" . $whereSQL);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];

            // Fetch data
            $sql = "SELECT * FROM anomaly_alerts" . $whereSQL . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            jsonResponse([
                'data' => $stmt->fetchAll(),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => intval($total),
                    'total_pages' => max(1, ceil($total / $limit)),
                ],
            ]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) jsonResponse(['error' => 'Invalid data'], 400);

            if (!empty($data['resolve_id'])) {
                $resolveId = intval($data['resolve_id']);
                if ($resolveId <= 0) jsonResponse(['error' => 'Invalid ID'], 400);

                $stmt = $db->prepare("UPDATE anomaly_alerts SET is_resolved = 1, resolved_at = NOW() WHERE id = ?");
                $stmt->execute([$resolveId]);
                jsonResponse(['success' => true]);
            } elseif (!empty($data['resolve_all'])) {
                // Resolve all filtered
                $type = $data['type'] ?? null;
                $sql = "UPDATE anomaly_alerts SET is_resolved = 1, resolved_at = NOW() WHERE is_resolved = 0";
                $params = [];
                if ($type && $type !== 'all') {
                    $sql .= " AND alert_type = ?";
                    $params[] = $type;
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                jsonResponse(['success' => true, 'resolved' => $stmt->rowCount()]);
            } else {
                jsonResponse(['error' => 'Invalid action'], 400);
            }
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    error_log("[API alerts] " . $e->getMessage());
    jsonResponse(['error' => 'เกิดข้อผิดพลาดของฐานข้อมูล'], 500);
}
