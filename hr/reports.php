<?php
/**
 * HR Reports
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireHR($db);

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Build query
$sql = "SELECT * FROM view_expenses_detail WHERE date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
$sql .= " ORDER BY date DESC";
$expenses = $db->fetchAll($sql, $params);

// Calculate totals
$totalAmount = array_sum(array_column($expenses, 'amount'));
$approvedAmount = array_sum(array_filter($expenses, function($e) { return in_array($e['status'], ['approved', 'released']); }));
$rejectedAmount = array_sum(array_filter($expenses, function($e) { return $e['status'] === 'rejected'; }));

$financialSummary = getFinancialSummary($db);

// Log report generation
logReportGenerated($db, 'HR Expense Report');

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'HR Reports';
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
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --sidebar-width: 260px; }
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
        
        .header-actions { display: flex; align-items: center; gap: 15px; }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .user-details { text-align: right; }
        .user-details .name { font-size: 14px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 12px; color: #666; }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        
        .filter-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; font-size: 12px; }
        .form-group input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-title { font-size: 12px; color: #666; margin-bottom: 8px; }
        .card-amount { font-size: 20px; font-weight: 700; color: var(--dark); }
        
        .table-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        table th { font-size: 11px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        
        .status-badge { padding: 4px 10px; border-radius: 15px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-released { background: #cce5ff; color: #004085; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .filter-form { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-school"></i></div>
            <h2><?php echo $siteName; ?></h2>
            <p>HR Manager Portal</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">Main Menu</div>
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="pending_expenses.php" class="menu-item"><i class="fas fa-clipboard-check"></i><span>Pending Approvals</span></a>
            <a href="approved_expenses.php" class="menu-item"><i class="fas fa-check-circle"></i><span>Approved</span></a>
            <a href="reports.php" class="menu-item active"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-chart-bar"></i> <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-details">
                        <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                        <div class="role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role_name'])); ?></div>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo $dateTo; ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
<a href="../admin/generate_pdf.php?date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Generate PDF</a>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="cards-grid">
            <div class="card">
                <div class="card-title">Opening Balance</div>
                <div class="card-amount"><?php echo number_format($financialSummary['total_opening_balance'], 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-title">Total Expenses</div>
                <div class="card-amount"><?php echo number_format($totalAmount, 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-title">Approved</div>
                <div class="card-amount"><?php echo number_format($approvedAmount, 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-title">Rejected</div>
                <div class="card-amount"><?php echo number_format($rejectedAmount, 2); ?> QAR</div>
            </div>
            <div class="card">
                <div class="card-title">Remaining</div>
                <div class="card-amount"><?php echo number_format($financialSummary['remaining_balance'], 2); ?> QAR</div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Voucher No</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($expenses) > 0): ?>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($expense['voucher_no']); ?></strong></td>
                            <td><?php echo formatDate($expense['date']); ?></td>
                            <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($expense['description'], 0, 30)); ?>...</td>
                            <td><strong><?php echo number_format($expense['amount'], 2); ?></strong></td>
                            <td><span class="status-badge status-<?php echo $expense['status']; ?>"><?php echo ucfirst($expense['status']); ?></span></td>
                            <td><?php echo $expense['hr_approver_name'] ? htmlspecialchars($expense['hr_approver_name']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 30px;">No expenses found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

