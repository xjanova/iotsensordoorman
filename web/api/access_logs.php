<?php
/**
 * API: ประวัติการเข้า-ออก
 */
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') jsonResponse(['ok' => true]);

// ============================================================
// DELETE: ลบประวัติ (เลือกรายการ หรือทั้งหมด)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'delete')) {
    try {
        $db = getDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        if (!empty($input['ids']) && is_array($input['ids'])) {
            // ลบเฉพาะ ID ที่เลือก
            $ids = array_map('intval', $input['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM access_logs WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            jsonResponse(['success' => true, 'deleted' => $stmt->rowCount(), 'message' => "ลบ {$stmt->rowCount()} รายการ"]);
        } elseif (($input['all'] ?? false) === true) {
            // ลบทั้งหมด
            $stmt = $db->query("DELETE FROM access_logs");
            jsonResponse(['success' => true, 'deleted' => $stmt->rowCount(), 'message' => "ลบทั้งหมด {$stmt->rowCount()} รายการ"]);
        } else {
            jsonResponse(['error' => 'ระบุ ids (array) หรือ all: true'], 400);
        }
    } catch (PDOException $e) {
        error_log("[API access_logs DELETE] " . $e->getMessage());
        jsonResponse(['error' => 'เกิดข้อผิดพลาด'], 500);
    }
}

try {
    $db = getDB();

    $limit = min(max(intval($_GET['limit'] ?? 20), 1), 500);
    $page = max(intval($_GET['page'] ?? 1), 1);
    $offset = ($page - 1) * $limit;

    $date = $_GET['date'] ?? null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $direction = $_GET['direction'] ?? null;
    $authorized = $_GET['authorized'] ?? null;
    $employee = $_GET['employee'] ?? null;
    $search = $_GET['search'] ?? null;

    $where = [];
    $params = [];

    if ($date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            jsonResponse(['error' => 'รูปแบบวันที่ไม่ถูกต้อง (YYYY-MM-DD)'], 400);
        }
        $where[] = "DATE(al.created_at) = ?";
        $params[] = $date;
    }

    if ($dateFrom) {
        $where[] = "al.created_at >= ?";
        $params[] = $dateFrom . ' 00:00:00';
    }

    if ($dateTo) {
        $where[] = "al.created_at <= ?";
        $params[] = $dateTo . ' 23:59:59';
    }

    if ($direction && in_array($direction, ['IN', 'OUT'])) {
        $where[] = "al.direction = ?";
        $params[] = $direction;
    }

    if ($authorized !== null && $authorized !== '') {
        $where[] = "al.is_authorized = ?";
        $params[] = intval($authorized);
    }

    if ($employee && $employee !== 'all') {
        if ($employee === 'unknown') {
            $where[] = "al.employee_id IS NULL";
        } else {
            $where[] = "al.employee_id = ?";
            $params[] = intval($employee);
        }
    }

    if ($search) {
        $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.emp_code LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereSQL = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM access_logs al LEFT JOIN employees e ON al.employee_id = e.id" . $whereSQL);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];

    // Fetch data
    $sql = "SELECT al.*, e.first_name, e.last_name, e.emp_code, e.department
            FROM access_logs al
            LEFT JOIN employees e ON al.employee_id = e.id"
            . $whereSQL
            . " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
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
} catch (PDOException $e) {
    error_log("[API access_logs] " . $e->getMessage());
    jsonResponse(['error' => 'เกิดข้อผิดพลาดของฐานข้อมูล'], 500);
}
