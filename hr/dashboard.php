<?php
/**
 * HR Dashboard
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireHR($db);

// Get data for dashboard
$financialSummary = getFinancialSummary($db);
$pendingExpenses = getExpensesByStatus($db, 'pending');
$recentExpenses = getAllExpenses($db, 10);
$statusCounts = getStatusCounts($db);
$pendingCount = count($pendingExpenses);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$adminName = getSetting($db, 'admin_name', ADMIN_NAME);
$hrName = getSetting($db, 'hr_name', HR_NAME);
$pageTitle = 'HR Dashboard';
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --light: #f8f9fa; --sidebar-width: 260px; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; min-height: 100vh; overflow-x: hidden; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%); color: white; overflow-y: auto; z-index: 1000; transform: translateX(-100%); transition: transform 0.3s ease; }
        .sidebar.active { transform: translateX(0); }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { width: 70px; height: 70px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 30px; }
        .sidebar-header h2 { font-size: 16px; font-weight: 600; margin-bottom: 3px; }
        .sidebar-header p { font-size: 11px; opacity: 0.8; }
        .sidebar-menu { padding: 15px 0; }
        .menu-section { padding: 8px 20px; font-size: 11px; text-transform: uppercase; opacity: 0.6; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: white; border-left: 3px solid transparent; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: var(--warning); }
        .menu-item i { width: 25px; font-size: 16px; flex-shrink: 0; }
        .menu-item span { font-size: 14px; margin-left: 10px; }
        .menu-item .badge { margin-left: auto; background: var(--danger); padding: 2px 8px; border-radius: 10px; font-size: 11px; flex-shrink: 0; }
        
        .menu-toggle { display: flex; position: fixed; top: 15px; left: 15px; z-index: 1100; width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; border-radius: 8px; color: white; font-size: 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3); align-items: center; justify-content: center; }
        .menu-toggle.active { left: calc(var(--sidebar-width) + 15px); }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 950; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }
        
        .main-content { margin-left: var(--sidebar-width); padding: 70px 15px 20px 15px; min-height: 100vh; width: calc(100% - var(--sidebar-width)); overflow-x: visible; }
        
        .top-header { background: white; padding: 12px 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 12px; }
        .top-header h1 { font-size: 18px; color: var(--dark); margin-left: 35px; flex: 1; min-width: 0; }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 36px; height: 36px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; flex-shrink: 0; font-size: 14px; }
        .user-details { display: none; text-align: right; }
        .user-details .name { font-size: 13px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 11px; color: #666; }
        
        .cards-grid { display: grid; grid-template-columns: 1fr; gap: 15px; margin-bottom: 20px; }
        .card { background: white; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); word-wrap: break-word; overflow: hidden; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .card-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .card-icon.yellow { background: rgba(255, 193, 7, 0.1); color: #d39e00; }
        .card-icon.green { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .card-icon.red { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .card-icon.blue { background: rgba(30, 60, 114, 0.1); color: var(--primary); }
        .card-title { font-size: 12px; color: #666; margin-bottom: 6px; }
        .card-amount { font-size: 20px; font-weight: 700; color: var(--dark); word-wrap: break-word; }
        
        .table-card { background: white; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; width: 100%; max-width: 100%; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .table-header h3 { font-size: 15px; color: var(--dark); }
        
        .btn { padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border: none; transition: all 0.3s; white-space: nowrap; max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--secondary); }
        
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        table th, table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #eee; white-space: nowrap; font-size: 12px; }
        table th { font-size: 11px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        table td { font-size: 12px; color: var(--dark); }
        
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; max-width: 100%; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; text-transform: uppercase; white-space: nowrap; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-released { background: #cce5ff; color: #004085; }
        
        /* Responsive Styles - Mobile First */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; }
            .menu-toggle { display: flex; }
        }
        
        @media (min-width: 481px) {
            .main-content { padding: 75px 20px 20px 20px; }
            .cards-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
            .card { padding: 18px; }
            .card-amount { font-size: 22px; }
            .table-card { padding: 18px; }
            .user-details { display: block; }
            .user-details .name { font-size: 14px; }
            .user-details .role { font-size: 12px; }
            .menu-toggle { top: 15px; left: 15px; }
            .menu-toggle.active { left: calc(var(--sidebar-width) + 15px); }
            .top-header { padding: 15px 18px; }
            .top-header h1 { font-size: 20px; margin-left: 40px; }
            .user-avatar { width: 40px; height: 40px; font-size: 16px; }
        }
        
        @media (min-width: 769px) {
            .sidebar { transform: translateX(0); }
            .main-content { margin-left: var(--sidebar-width); padding: 25px; }
            .menu-toggle { display: none; }
            .top-header { padding: 15px 25px; margin-bottom: 25px; }
            .top-header h1 { font-size: 22px; margin-left: 0; }
            .cards-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
            .card { padding: 20px; }
            .card-header { margin-bottom: 15px; }
            .card-icon { width: 45px; height: 45px; font-size: 20px; }
            .card-title { font-size: 13px; margin-bottom: 8px; }
            .card-amount { font-size: 24px; }
            .table-card { padding: 20px; margin-bottom: 25px; }
            .table-header { margin-bottom: 20px; }
            .table-header h3 { font-size: 16px; }
            table th, table td { padding: 12px; font-size: 13px; }
            table th { font-size: 12px; }
            .btn { padding: 10px 18px; font-size: 13px; }
        }
        
        @media (max-width: 360px) {
            .cards-grid { gap: 10px; }
            .card { padding: 12px; }
            .card-amount { font-size: 18px; }
            .table-card { padding: 12px; }
            .table-header h3 { font-size: 13px; }
            .btn { padding: 6px 10px; font-size: 11px; }
        }
        </style>
    </head>
    <body>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo"><i class="fas fa-school"></i></div>
                <h2><?php echo $siteName; ?></h2>
                <p>HR Manager Portal</p>
            </div>
            <div class="sidebar-menu">
                <div class="menu-section">Main Menu</div>
                <a href="dashboard.php" class="menu-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="pending_expenses.php" class="menu-item">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Pending Approvals</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="approved_expenses.php" class="menu-item"><i class="fas fa-check-circle"></i><span>Approved</span></a>
                <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
                <div class="menu-section">Account</div>
                <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>

        <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-tachometer-alt"></i> <?php echo $pageTitle; ?></h1>
            <div class="user-info">
                <div class="user-details">
                    <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                    <div class="role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role_name'])); ?></div>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="cards-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon yellow"><i class="fas fa-clock"></i></div>
                </div>
                <div class="card-title">Pending Approval</div>
                <div class="card-amount"><?php echo number_format($financialSummary['pending_approval_amount'], 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-icon green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="card-title">Approved by Me</div>
                <div class="card-amount"><?php echo number_format($financialSummary['approved_not_released_amount'], 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-icon blue"><i class="fas fa-money-bill-wave"></i></div>
                </div>
                <div class="card-title">Total Released</div>
                <div class="card-amount"><?php echo number_format($financialSummary['total_released_amount'], 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-icon red"><i class="fas fa-coins"></i></div>
                </div>
                <div class="card-title">Remaining Balance</div>
                <div class="card-amount"><?php echo number_format($financialSummary['remaining_balance'], 2); ?> QAR</div>
            </div>
        </div>

        <!-- Pending Expenses Count -->
        <?php if ($pendingCount > 0): ?>
        <div class="table-card" style="border-left: 4px solid var(--warning);">
            <div class="table-header">
                <h3><i class="fas fa-exclamation-circle" style="color: var(--warning);"></i> Attention Required - <?php echo $pendingCount; ?> Pending Expenses</h3>
                <a href="pending_expenses.php" class="btn btn-primary">Review Now</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Expenses -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Recent Expenses</h3>
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
                            <td><?php echo htmlspecialchars(substr($expense['description'], 0, 40)); ?>...</td>
                            <td><strong><?php echo number_format($expense['amount'], 2); ?> QAR</strong></td>
                            <td><span class="status-badge status-<?php echo $expense['status']; ?>"><?php echo ucfirst($expense['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 30px;">No expenses found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/responsive.js"></script>
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                menuToggle.classList.toggle('active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('active');
                }
            });

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    menuToggle.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                });
            }
        }
    </script>
</body>
</html>

