<?php
/**
 * Release Funds
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireAdmin($db);

$error = '';
$success = '';

// Process release funds
if (isset($_POST['action']) && $_POST['action'] === 'release') {
    $expenseId = (int)$_POST['expense_id'];
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $expense = getExpenseById($db, $expenseId);
        
        if (!$expense) {
            $error = 'Expense not found.';
        } elseif ($expense['status'] !== 'approved') {
            $error = 'Only approved expenses can have funds released.';
        } else {
            // Check balance
            $financialSummary = getFinancialSummary($db);
            $remainingBalance = (float)$financialSummary['remaining_balance'];
            
            if ($remainingBalance < $expense['amount']) {
                $error = 'Insufficient balance. Current remaining: ' . number_format($remainingBalance, 2) . ' QAR';
            } else {
                // Release funds
                $db->query("UPDATE expenses SET status = 'released', admin_released_by = ?, release_date = NOW() WHERE id = ?",
                    [$_SESSION['user_id'], $expenseId]);
                
                logFundRelease($db, $expenseId, $expense['voucher_no'], $expense['amount']);
                
                // Add notification
                addNotification($db, $expense['created_by'], 'Funds Released', 
                    "Funds of " . number_format($expense['amount'], 2) . " QAR have been released for {$expense['voucher_no']}.", 
                    'expenses.php');
                
                $success = 'Funds released successfully for ' . $expense['voucher_no'];
            }
        }
    }
}

// Get approved expenses (ready for release)
$approvedExpenses = getExpensesByStatus($db, 'approved');
$financialSummary = getFinancialSummary($db);
$remainingBalance = (float)$financialSummary['remaining_balance'];
$warningBalance = (float)getSetting($db, 'warning_balance', WARNING_BALANCE);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Release Funds';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $siteName; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --light: #f8f9fa; --white: #ffffff; --sidebar-width: 260px; }
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
        .card-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 15px; }
        .card-icon.blue { background: rgba(30, 60, 114, 0.1); color: var(--primary); }
        .card-icon.green { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .card-icon.red { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .card-title { font-size: 13px; color: #666; margin-bottom: 8px; }
        .card-amount { font-size: 24px; font-weight: 700; color: var(--dark); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border-left: 4px solid #c33; }
        .alert-success { background: #efe; color: #3c3; border-left: 4px solid #3c3; }
        .alert-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        
        .table-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table-card h3 { font-size: 16px; color: var(--dark); margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        table td { font-size: 13px; color: var(--dark); }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #218838; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-approved { background: #d4edda; color: #155724; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .table-card { overflow-x: auto; }
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
            <a href="release_funds.php" class="menu-item active"><i class="fas fa-money-bill-wave"></i><span>Release Funds</span><?php if (count($approvedExpenses) > 0): ?><span class="badge"><?php echo count($approvedExpenses); ?></span><?php endif; ?></a>
            <div class="menu-section">Reports</div>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="activity_logs.php" class="menu-item"><i class="fas fa-history"></i><span>Activity Logs</span></a>
            <div class="menu-section">Settings</div>
            <a href="users.php" class="menu-item"><i class="fas fa-users"></i><span>Manage Users</span></a>
            <a href="backup.php" class="menu-item"><i class="fas fa-database"></i><span>Backup</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-money-bill-wave"></i> <?php echo $pageTitle; ?></h1>
            <div class="user-info">
                <div class="user-details">
                    <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                    <div class="role"><?php echo ucfirst($_SESSION['role_name']); ?></div>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($remainingBalance < $warningBalance): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Warning: Remaining Balance is below <?php echo number_format($warningBalance); ?> QAR!
            </div>
        <?php endif; ?>

        <!-- Financial Summary -->
        <div class="cards-grid">
            <div class="card">
                <div class="card-icon blue"><i class="fas fa-wallet"></i></div>
                <div class="card-title">Opening Balance</div>
                <div class="card-amount"><?php echo number_format($financialSummary['total_opening_balance'], 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="card-title">Approved (Not Released)</div>
                <div class="card-amount"><?php echo number_format($financialSummary['approved_not_released_amount'], 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-icon red"><i class="fas fa-coins"></i></div>
                <div class="card-title">Remaining Balance</div>
                <div class="card-amount"><?php echo number_format($remainingBalance, 2); ?> QAR</div>
            </div>
        </div>

        <!-- Approved Expenses -->
        <div class="table-card">
            <h3><i class="fas fa-check-circle"></i> Approved Expenses - Ready for Release</h3>
            <?php if (count($approvedExpenses) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Voucher No</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>HR Approved By</th>
                        <th>Approval Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvedExpenses as $expense): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($expense['voucher_no']); ?></strong></td>
                        <td><?php echo formatDate($expense['date']); ?></td>
                        <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($expense['description'], 0, 40)); ?>...</td>
                        <td><strong><?php echo number_format($expense['amount'], 2); ?> QAR</strong></td>
                        <td><?php echo htmlspecialchars($expense['hr_approver_name'] ?? '-'); ?></td>
                        <td><?php echo formatDateTime($expense['hr_approval_date']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="release">
                                <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to release funds for <?php echo $expense['voucher_no']; ?>?')">
                                    <i class="fas fa-check"></i> Release
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                    No approved expenses pending release
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

