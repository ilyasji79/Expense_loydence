<?php
/**
 * Activity Logger Functions
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/db.php';

// Log activity
function logActivity($db, $userId, $action, $details = '') {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $db->query("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
        [$userId, $action, $details, $ipAddress, $userAgent]);
}

// Log expense creation
function logExpenseCreated($db, $expenseId, $voucherNo, $amount) {
    logActivity($db, $_SESSION['user_id'], 'expense_created', 
        "Created expense voucher: $voucherNo, Amount: $amount QAR");
}

// Log expense update
function logExpenseUpdated($db, $expenseId, $voucherNo, $changes) {
    logActivity($db, $_SESSION['user_id'], 'expense_updated', 
        "Updated expense voucher: $voucherNo, Changes: $changes");
}

// Log expense deletion
function logExpenseDeleted($db, $expenseId, $voucherNo) {
    logActivity($db, $_SESSION['user_id'], 'expense_deleted', 
        "Deleted expense voucher: $voucherNo");
}

// Log HR approval
function logApproval($db, $expenseId, $voucherNo, $amount, $approved = true) {
    $action = $approved ? 'expense_approved' : 'expense_rejected';
    $status = $approved ? 'Approved' : 'Rejected';
    
    logActivity($db, $_SESSION['user_id'], $action, 
        "$status expense: $voucherNo, Amount: $amount QAR");
}

// Log fund release
function logFundRelease($db, $expenseId, $voucherNo, $amount) {
    logActivity($db, $_SESSION['user_id'], 'funds_released', 
        "Released funds for voucher: $voucherNo, Amount: $amount QAR");
}

// Log opening balance update
function logOpeningBalanceUpdate($db, $amount) {
    logActivity($db, $_SESSION['user_id'], 'opening_balance_updated', 
        "Updated opening balance to: $amount QAR");
}

// Log report generation
function logReportGenerated($db, $reportType) {
    logActivity($db, $_SESSION['user_id'], 'report_generated', 
        "Generated $reportType report");
}

// Log backup
function logBackup($db, $backupFile) {
    logActivity($db, $_SESSION['user_id'], 'database_backup', 
        "Created database backup: $backupFile");
}

// Log user action
function logUserAction($db, $action, $details) {
    logActivity($db, $_SESSION['user_id'], $action, $details);
}

// Get activity log with filters
function getFilteredActivityLogs($db, $userId = null, $action = null, $limit = 100) {
    $sql = "SELECT al.*, u.full_name as user_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            WHERE 1=1";
    
    $params = [];
    
    if ($userId) {
        $sql .= " AND al.user_id = ?";
        $params[] = $userId;
    }
    
    if ($action) {
        $sql .= " AND al.action = ?";
        $params[] = $action;
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return $db->fetchAll($sql, $params);
}

// Clean old logs (keep last 90 days)
function cleanOldLogs($db) {
    $db->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
}

// Get activity summary for dashboard
function getActivitySummary($db, $days = 7) {
    return $db->fetchAll("
        SELECT 
            DATE(created_at) as date,
            action,
            COUNT(*) as count
        FROM activity_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at), action
        ORDER BY date DESC, action
    ", [$days]);
}

// Get recent activities
function getRecentActivities($db, $limit = 20) {
    return $db->fetchAll("
        SELECT al.*, u.full_name as user_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT ?
    ", [$limit]);
}

