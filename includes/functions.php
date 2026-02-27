<?php
/**
 * Helper Functions
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/db.php';

// Generate voucher number
function generateVoucherNo($db) {
    $date = date('Ymd');
    $stmt = $db->query("SELECT COUNT(*) as count FROM expenses WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch();
    $count = $result['count'] + 1;
    return 'EXP-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// Format currency
function formatCurrency($amount, $currency = 'QAR') {
    return number_format($amount, 2) . ' ' . $currency;
}

// Get current user info
function getCurrentUser($db) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// Check if user has role
function hasRole($db, $roleName) {
    $user = getCurrentUser($db);
    if (!$user) return false;
    
    $role = $db->fetch("SELECT role_name FROM roles WHERE id = ?", [$user['role_id']]);
    return $role && $role['role_name'] === $roleName;
}

// Get financial summary
function getFinancialSummary($db) {
    $result = $db->fetch("SELECT * FROM view_financial_summary");
    return $result ?: [
        'total_opening_balance' => 0,
        'total_expenses' => 0,
        'pending_approval_amount' => 0,
        'approved_not_released_amount' => 0,
        'total_released_amount' => 0,
        'remaining_balance' => 0
    ];
}

// Get expenses by status
function getExpensesByStatus($db, $status) {
    return $db->fetchAll("SELECT * FROM view_expenses_detail WHERE status = ? ORDER BY created_at DESC", [$status]);
}

// Get all expenses
function getAllExpenses($db, $limit = 100) {
    return $db->fetchAll("SELECT * FROM view_expenses_detail ORDER BY date DESC, id DESC LIMIT ?", [$limit]);
}

// Get expense by ID
function getExpenseById($db, $id) {
    return $db->fetch("SELECT * FROM view_expenses_detail WHERE id = ?", [$id]);
}

// Get categories
function getCategories($db, $activeOnly = true) {
    $sql = "SELECT * FROM categories";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    return $db->fetchAll($sql);
}

// Get category by ID
function getCategoryById($db, $id) {
    return $db->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
}

// Get users by role
function getUsersByRole($db, $roleId) {
    return $db->fetchAll("SELECT * FROM users WHERE role_id = ? AND is_active = 1", [$roleId]);
}

// Get activity logs
function getActivityLogs($db, $limit = 50) {
    return $db->fetchAll("
        SELECT al.*, u.full_name as user_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT ?
    ", [$limit]);
}

// Get pending approvals count
function getPendingApprovalsCount($db) {
    $result = $db->fetch("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
    return $result['count'] ?? 0;
}

// Get notifications for user
function getNotifications($db, $userId, $limit = 10) {
    return $db->fetchAll("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ", [$userId, $limit]);
}

// Add notification
function addNotification($db, $userId, $title, $message, $link = '') {
    $db->query("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)", 
        [$userId, $title, $message, $link]);
}

// Get monthly expense data for charts
function getMonthlyExpenses($db, $year = null) {
    $year = $year ?: date('Y');
    return $db->fetchAll("
        SELECT 
            MONTH(date) as month,
            SUM(CASE WHEN status = 'released' THEN amount ELSE 0 END) as released
        FROM expenses 
        WHERE YEAR(date) = ?
        GROUP BY MONTH(date)
        ORDER BY month
    ", [$year]);
}

// Get category distribution for pie chart
function getCategoryDistribution($db) {
    return $db->fetchAll("
        SELECT 
            c.category_name,
            COALESCE(SUM(e.amount), 0) as total
        FROM categories c
        LEFT JOIN expenses e ON c.id = e.category_id AND e.status = 'released'
        GROUP BY c.id, c.category_name
        HAVING total > 0
    ");
}

// Get status counts
function getStatusCounts($db) {
    return $db->fetchAll("
        SELECT status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total
        FROM expenses
        GROUP BY status
    ");
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate date
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Redirect with message
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit;
}

// Show message
function showMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $message = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        $colors = [
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#17a2b8'
        ];
        
        return "<div style='padding: 12px 20px; margin: 10px 0; border-radius: 5px; background-color: {$colors[$type]}; color: white;'>$message</div>";
    }
    return '';
}

// CSRF Token
function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get settings
function getSetting($db, $key, $default = '') {
    $result = $db->fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

// Update setting
function updateSetting($db, $key, $value) {
    $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
}

// Get opening balance
function getOpeningBalance($db) {
    $result = $db->fetch("SELECT SUM(amount) as total FROM opening_balance");
    return $result['total'] ?? 0;
}

// Date format for display
function formatDate($date, $format = 'd M Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

// DateTime format for display
function formatDateTime($datetime, $format = 'd M Y h:i A') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

