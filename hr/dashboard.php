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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/responsive.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --light: #f8f9fa; --sidebar-width: 260px; }
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
        .menu-item .badge { margin-left: auto; background: var(--danger); padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        
        .main-content { margin-left: var(--sidebar-width); padding: 20px; min-height: 100vh; }
        
        .top-header { background: white; padding: 15px 25px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-header h1 { font-size: 22px; color: var(--dark); }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .user-details { text-align: right; }
        .user-details .name { font-size: 14px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 12px; color: #666; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .card-icon.yellow { background: rgba(255, 193, 7, 0.1); color: #d39e00; }
        .card-icon.green { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .card-icon.red { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .card-icon.blue { background: rgba(30, 60, 114, 0.1); color: var(--primary); }
        .card-title { font-size: 13px; color: #666; margin-bottom: 8px; }
        .card-amount { font-size: 24px; font-weight: 700; color: var(--dark); }
        
        .table-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .table-header h3 { font-size: 16px; color: var(--dark); }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--secondary); }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        table td { font-size: 13px; color: var(--dark); }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-released { background: #cce5ff; color: #004085; }
        </style>
    </head>
    <body>
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
</body>
</html>

