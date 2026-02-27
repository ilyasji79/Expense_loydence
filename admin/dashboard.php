<?php
/**
 * Admin Dashboard
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireAdmin($db);

// Get data for dashboard
$financialSummary = getFinancialSummary($db);
$pendingExpenses = getExpensesByStatus($db, 'pending');
$approvedExpenses = getExpensesByStatus($db, 'approved');
$recentExpenses = getAllExpenses($db, 10);
$statusCounts = getStatusCounts($db);
$categoryDistribution = getCategoryDistribution($db);
$monthlyExpenses = getMonthlyExpenses($db);
$recentActivities = getRecentActivities($db, 10);
$pendingCount = getPendingApprovalsCount($db);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$adminName = getSetting($db, 'admin_name', ADMIN_NAME);
$hrName = getSetting($db, 'hr_name', HR_NAME);
$warningBalance = (float)getSetting($db, 'warning_balance', WARNING_BALANCE);
$remainingBalance = (float)$financialSummary['remaining_balance'];

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $siteName; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/responsive.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1e3c72;
            --secondary: #2a5298;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --dark: #343a40;
            --light: #f8f9fa;
            --white: #ffffff;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header .logo {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 30px;
        }

        .sidebar-header h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .sidebar-header p {
            font-size: 11px;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 15px 0;
        }

        .menu-section {
            padding: 8px 20px;
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.6;
            letter-spacing: 1px;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            border-left: 3px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--warning);
        }

        .menu-item i {
            width: 25px;
            font-size: 16px;
        }

        .menu-item span {
            font-size: 14px;
        }

        .menu-item .badge {
            margin-left: auto;
            background: var(--danger);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }

        /* Top Header */
        .top-header {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .top-header h1 {
            font-size: 22px;
            color: var(--dark);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--dark);
            cursor: pointer;
            padding: 8px;
        }

        .notification-btn .badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--danger);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-details {
            text-align: right;
        }

        .user-details .name {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }

        .user-details .role {
            font-size: 12px;
            color: #666;
        }

        /* Financial Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .card-icon.blue { background: rgba(30, 60, 114, 0.1); color: var(--primary); }
        .card-icon.yellow { background: rgba(255, 193, 7, 0.1); color: #d39e00; }
        .card-icon.green { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .card-icon.red { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .card-icon.purple { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }

        .card-title {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .card-amount {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .card-amount.warning { color: var(--danger); }

        /* Alert */
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-box i {
            color: #856404;
            font-size: 20px;
        }

        .alert-box p {
            color: #856404;
            font-weight: 500;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h3 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .chart-container {
            position: relative;
            height: 250px;
        }

        /* Tables */
        .table-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h3 {
            font-size: 16px;
            color: var(--dark);
        }

        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        table th {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
        }

        table td {
            font-size: 13px;
            color: var(--dark);
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-released { background: #cce5ff; color: #004085; }

        /* Status Counters */
        .status-counters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .status-counter {
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-counter .count {
            font-size: 20px;
            font-weight: 700;
        }

        .status-counter.pending { background: #fff3cd; }
        .status-counter.approved { background: #d4edda; }
        .status-counter.rejected { background: #f8d7da; }
        .status-counter.released { background: #cce5ff; }

        /* Activity Log */
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .activity-icon.create { background: #d4edda; color: var(--success); }
        .activity-icon.approve { background: #cce5ff; color: var(--info); }
        .activity-icon.release { background: #e2e3e5; color: var(--dark); }
        .activity-icon.delete { background: #f8d7da; color: var(--danger); }

        .activity-content {
            flex: 1;
        }

        .activity-content p {
            font-size: 13px;
            color: var(--dark);
            margin-bottom: 3px;
        }

        .activity-content span {
            font-size: 11px;
            color: #999;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .menu-toggle {
                display: block;
            }
        }

        @media (max-width: 576px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .top-header {
                flex-direction: column;
                gap: 15px;
            }

            .table-card {
                overflow-x: auto;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-school"></i>
            </div>
            <h2><?php echo $siteName; ?></h2>
            <p>Expense Management ERP</p>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-section">Main Menu</div>
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="expenses.php" class="menu-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Expenses</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="add_expense.php" class="menu-item">
                <i class="fas fa-plus-circle"></i>
                <span>Add Expense</span>
            </a>
            <a href="opening_balance.php" class="menu-item">
                <i class="fas fa-wallet"></i>
                <span>Opening Balance</span>
            </a>
            <a href="release_funds.php" class="menu-item">
                <i class="fas fa-money-bill-wave"></i>
                <span>Release Funds</span>
                <?php if (count($approvedExpenses) > 0): ?>
                    <span class="badge"><?php echo count($approvedExpenses); ?></span>
                <?php endif; ?>
            </a>
            
            <div class="menu-section">Reports</div>
            <a href="reports.php" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="activity_logs.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
            
            <div class="menu-section">Settings</div>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
            <a href="backup.php" class="menu-item">
                <i class="fas fa-database"></i>
                <span>Backup</span>
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1><i class="fas fa-tachometer-alt"></i> <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </button>
                <div class="user-info">
                    <div class="user-details">
                        <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                        <div class="role"><?php echo ucfirst($_SESSION['role_name']); ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert for low balance -->
        <?php if ($remainingBalance < $warningBalance): ?>
            <div class="alert-box">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Warning: Remaining Balance is below <?php echo formatCurrency($warningBalance); ?>! Current: <?php echo formatCurrency($remainingBalance); ?></p>
            </div>
        <?php endif; ?>

        <!-- Financial Cards -->
        <div class="cards-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon blue">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="card-title">Opening Balance</div>
                <div class="card-amount"><?php echo formatCurrency($financialSummary['total_opening_balance']); ?></div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-icon yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="card-title">Pending HR Approval</div>
                <div class="card-amount"><?php echo formatCurrency($financialSummary['pending_approval_amount']); ?></div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-icon purple">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="card-title">Approved Not Released</div>
                <div class="card-amount"><?php echo formatCurrency($financialSummary['approved_not_released_amount']); ?></div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-icon green">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="card-title">Total Released</div>
                <div class="card-amount"><?php echo formatCurrency($financialSummary['total_released_amount']); ?></div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-icon <?php echo $remainingBalance < $warningBalance ? 'red' : 'green'; ?>">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="card-title">Remaining Balance</div>
                <div class="card-amount <?php echo $remainingBalance < $warningBalance ? 'warning' : ''; ?>">
                    <?php echo formatCurrency($remainingBalance); ?>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Expense Category Distribution</h3>
                <div class="chart-container">
                    <canvas id="categoryPieChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Monthly Released Funds</h3>
                <div class="chart-container">
                    <canvas id="monthlyBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Status Counters -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-tasks"></i> Approval Status Overview</h3>
            </div>
            <div class="status-counters">
                <?php 
                $statusData = [
                    'pending' => ['Pending', 'yellow', 'fa-clock'],
                    'approved' => ['Approved', 'green', 'fa-check'],
                    'rejected' => ['Rejected', 'red', 'fa-times'],
                    'released' => ['Released', 'blue', 'fa-check-circle']
                ];
                
                $counts = array_fill_keys(['pending', 'approved', 'rejected', 'released'], ['count' => 0, 'total' => 0]);
                foreach ($statusCounts as $sc) {
                    $counts[$sc['status']] = $sc;
                }
                ?>
                
                <div class="status-counter pending">
                    <i class="fas fa-clock"></i>
                    <div>
                        <div class="count"><?php echo $counts['pending']['count']; ?></div>
                        <small>Pending</small>
                    </div>
                </div>
                <div class="status-counter approved">
                    <i class="fas fa-check"></i>
                    <div>
                        <div class="count"><?php echo $counts['approved']['count']; ?></div>
                        <small>Approved</small>
                    </div>
                </div>
                <div class="status-counter rejected">
                    <i class="fas fa-times"></i>
                    <div>
                        <div class="count"><?php echo $counts['rejected']['count']; ?></div>
                        <small>Rejected</small>
                    </div>
                </div>
                <div class="status-counter released">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <div class="count"><?php echo $counts['released']['count']; ?></div>
                        <small>Released</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Recent Expenses</h3>
                <a href="expenses.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Voucher No</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentExpenses) > 0): ?>
                        <?php foreach ($recentExpenses as $expense): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($expense['voucher_no']); ?></strong></td>
                            <td><?php echo formatDate($expense['date']); ?></td>
                            <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($expense['description'], 0, 50)); ?>...</td>
                            <td><strong><?php echo formatCurrency($expense['amount']); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $expense['status']; ?>">
                                    <?php echo ucfirst($expense['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">No expenses found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Activity Log -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
                <a href="activity_logs.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="activity-list">
                <?php if (count($recentActivities) > 0): ?>
                    <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo strpos($activity['action'], 'created') !== false ? 'create' : (strpos($activity['action'], 'approved') !== false ? 'approve' : (strpos($activity['action'], 'released') !== false ? 'release' : 'delete')); ?>">
                            <i class="fas <?php 
                                echo strpos($activity['action'], 'created') !== false ? 'fa-plus' : 
                                    (strpos($activity['action'], 'approved') !== false ? 'fa-check' : 
                                    (strpos($activity['action'], 'released') !== false ? 'fa-money-bill-wave' : 'fa-edit')); 
                            ?>"></i>
                        </div>
                        <div class="activity-content">
                            <p><?php echo htmlspecialchars($activity['details']); ?></p>
                            <span><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?> - <?php echo formatDateTime($activity['created_at']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 20px; color: #999;">No recent activities</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/responsive.js"></script>
    <script>
        // Category Pie Chart
        const categoryData = <?php echo json_encode($categoryDistribution); ?>;
        const categoryLabels = categoryData.map(c => c.category_name);
        const categoryValues = categoryData.map(c => parseFloat(c.total));

        const pieCtx = document.getElementById('categoryPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: [
                        '#1e3c72', '#2a5298', '#28a745', '#ffc107', '#dc3545',
                        '#17a2b8', '#6f42c1', '#e83e8c', '#20c997', '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });

        // Monthly Bar Chart
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlyData = <?php echo json_encode($monthlyExpenses); ?>;
        
        const monthlyValues = new Array(12).fill(0);
        monthlyData.forEach(m => {
            monthlyValues[m.month - 1] = parseFloat(m.released);
        });

        const barCtx = document.getElementById('monthlyBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Released Funds (QAR)',
                    data: monthlyValues,
                    backgroundColor: '#1e3c72',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' QAR';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>

