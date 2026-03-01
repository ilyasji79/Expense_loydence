<?php
/**
 * Activity Logs
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireAdmin($db);

// Get filters
$userFilter = $_GET['user'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$sql = "SELECT al.*, u.full_name as user_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($userFilter) {
    $sql .= " AND al.user_id = ?";
    $params[] = $userFilter;
}
if ($actionFilter) {
    $sql .= " AND al.action = ?";
    $params[] = $actionFilter;
}
if ($dateFrom) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY al.created_at DESC LIMIT 200";
$logs = $db->fetchAll($sql, $params);

// Get users for filter
$users = $db->fetchAll("SELECT id, full_name FROM users WHERE is_active = 1");

// Get unique actions for filter
$actions = $db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Activity Logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $siteName; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/responsive.css">
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/responsive.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --dark: #343a40; --sidebar-width: 260px; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; min-height: 100vh; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%); color: white; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { width: 70px; height: 70px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 30px; }
        .sidebar-header h2 { font-size: 16px; font-weight: 600; margin-bottom: 3px; }
        .sidebar-header p { font-size: 11px; opacity: 0.8; }
        .sidebar-menu { padding: 15px 0; }
        .menu-section { padding: 8px 20px; font-size: 11px; text-transform: uppercase; opacity: 0.6; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: white; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: var(--warning); }
        .menu-item i { width: 25px; font-size: 16px; }
        .menu-item span { font-size: 14px; }
        
        .main-content { margin-left: var(--sidebar-width); padding: 20px; min-height: 100vh; }
        
        .top-header { background: white; padding: 15px 25px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-header h1 { font-size: 22px; color: var(--dark); }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .user-details { text-align: right; }
        .user-details .name { font-size: 14px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 12px; color: #666; }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        
        .filter-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; font-size: 12px; }
        .form-group select, .form-group input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
        
        .table-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        table td { font-size: 13px; color: var(--dark); }
        
        .action-badge { padding: 4px 10px; border-radius: 15px; font-size: 11px; font-weight: 500; }
        .action-create { background: #d4edda; color: #155724; }
        .action-approve { background: #cce5ff; color: #004085; }
        .action-release { background: #e2e3e5; color: #383d41; }
        .action-delete { background: #f8d7da; color: #721c24; }
        .action-login { background: #d1ecf1; color: #0c5460; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-school"></i></div>
            <h2><?php echo $siteName; ?></h2>
            <p>Expense Management ERP</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">Main Menu</div>
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="expenses.php" class="menu-item"><i class="fas fa-file-invoice-dollar"></i><span>Expenses</span></a>
            <a href="add_expense.php" class="menu-item"><i class="fas fa-plus-circle"></i><span>Add Expense</span></a>
            <a href="opening_balance.php" class="menu-item"><i class="fas fa-wallet"></i><span>Opening Balance</span></a>
            <a href="release_funds.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>Release Funds</span></a>
            <div class="menu-section">Reports</div>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="activity_logs.php" class="menu-item active"><i class="fas fa-history"></i><span>Activity Logs</span></a>
            <div class="menu-section">Settings</div>
            <a href="users.php" class="menu-item"><i class="fas fa-users"></i><span>Manage Users</span></a>
            <a href="backup.php" class="menu-item"><i class="fas fa-database"></i><span>Backup</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-history"></i> <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-details">
                        <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                        <div class="role"><?php echo ucfirst($_SESSION['role_name']); ?></div>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>User</label>
                    <select name="user">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $userFilter == $user['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Action</label>
                    <select name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?php echo $act['action']; ?>" <?php echo $actionFilter === $act['action'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($act['action']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo $dateTo; ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="activity_logs.php" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo formatDateTime($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                            <td>
                                <span class="action-badge <?php 
                                    echo strpos($log['action'], 'created') !== false ? 'action-create' : 
                                        (strpos($log['action'], 'approved') !== false ? 'action-approve' : 
                                        (strpos($log['action'], 'released') !== false ? 'action-release' : 
                                        (strpos($log['action'], 'deleted') !== false ? 'action-delete' : 'action-login')));
                                ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($log['action'], '_'))); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px;">No activity logs found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

