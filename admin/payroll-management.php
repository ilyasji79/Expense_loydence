<?php
/**
 * Admin - Payroll Management
 * Expense Management ERP - Loydence Academy
 * 
 * Admin can view and manage all payroll
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/employee_functions.php';
require_once __DIR__ . '/../includes/logger.php';

requireLogin();
if (!hasRole($db, 'admin')) {
    redirect(BASE_URL . '/dashboard.php', 'Access denied', 'error');
}

$message = '';
$messageType = '';

// Get filters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Generate salary for all employees if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request';
        $messageType = 'danger';
    } else {
        $employees = getAllEmployees($db, true);
        $generated = 0;
        foreach ($employees as $emp) {
            $result = generateSalary($db, $emp['id'], $year, $month);
            if ($result['success']) {
                $generated++;
            }
        }
        $message = "Salary generated for $generated employees";
        $messageType = 'success';
        
        logActivity($db, $_SESSION['user_id'], 'payroll_generated', "Payroll generated for $generated employees for $month/$year");
    }
}

// Get payroll data
$payrollData = getPayrollReport($db, $year, $month);

// Filter by department if specified
if ($department) {
    $payrollData = array_filter($payrollData, function($p) use ($department) {
        return $p['department'] === $department;
    });
}

// Get departments
$departments = getDepartments($db);

// Calculate totals
$totalBase = 0;
$totalGross = 0;
$totalNet = 0;
foreach ($payrollData as $p) {
    $totalBase += $p['base_salary'];
    $totalGross += $p['gross_salary'];
    $totalNet += $p['net_salary'];
}

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Payroll Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $siteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/responsive.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --sidebar-width: 260px; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; min-height: 100vh; overflow-x: hidden; }
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%); color: white; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { width: 70px; height: 70px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 30px; }
        .sidebar-header h2 { font-size: 16px; font-weight: 600; }
        .sidebar-header p { font-size: 11px; opacity: 0.8; }
        .sidebar-menu { padding: 15px 0; }
        .menu-section { padding: 8px 20px; font-size: 11px; text-transform: uppercase; opacity: 0.6; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; text-decoration: none; color: white; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: var(--warning); }
        .menu-item i { width: 25px; font-size: 16px; }
        .menu-item span { font-size: 14px; margin-left: 10px; }
        
        .main-content { margin-left: var(--sidebar-width); padding: 25px; min-height: 100vh; }
        .top-header { background: white; padding: 15px 25px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-header h1 { font-size: 22px; color: var(--dark); }
        
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h3 { font-size: 16px; color: var(--dark); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card .label { font-size: 12px; color: #666; text-transform: uppercase; }
        .stat-card .amount { font-size: 24px; font-weight: 700; color: var(--primary); margin-top: 5px; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; align-items: end; }
        .filter-form .form-group { margin-bottom: 0; }
        .filter-form select, .filter-form input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processed { background: #cce5ff; color: #004085; }
        .status-paid { background: #d4edda; color: #155724; }
        
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
            <p>Admin Portal</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">Main Menu</div>
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="expenses.php" class="menu-item"><i class="fas fa-receipt"></i><span>Expenses</span></a>
            <a href="opening_balance.php" class="menu-item"><i class="fas fa-wallet"></i><span>Opening Balance</span></a>
            <div class="menu-section">Employee Management</div>
            <a href="users.php" class="menu-item"><i class="fas fa-users"></i><span>Users</span></a>
            <a href="leave-management.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>Leave Management</span></a>
            <a href="payroll-management.php" class="menu-item active"><i class="fas fa-money-bill-wave"></i><span>Payroll</span></a>
            <div class="menu-section">Reports</div>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="activity_logs.php" class="menu-item"><i class="fas fa-history"></i><span>Activity Logs</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-money-bill-wave"></i> <?php echo $pageTitle; ?></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Employees</div>
                <div class="amount"><?php echo count($payrollData); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Base Salary</div>
                <div class="amount"><?php echo formatCurrency($totalBase); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Gross Salary</div>
                <div class="amount"><?php echo formatCurrency($totalGross); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Net Salary</div>
                <div class="amount"><?php echo formatCurrency($totalNet); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Year</label>
                    <select name="year">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Month</label>
                    <select name="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department == $dept['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>
            
            <!-- Generate Payroll Button (separate form) -->
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="action" value="generate">
                <input type="hidden" name="year" value="<?php echo $year; ?>">
                <input type="hidden" name="month" value="<?php echo $month; ?>">
                <button type="submit" class="btn btn-success" onclick="return confirm('Generate salary for all employees this month?');">
                    <i class="fas fa-cog"></i> Generate Payroll
                </button>
            </form>
        </div>

        <!-- Payroll Table -->
        <div class="card">
            <h3>Payroll Details - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Base Salary</th>
                            <th>Allowances</th>
                            <th>Overtime</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payrollData) > 0): ?>
                            <?php foreach ($payrollData as $sal): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sal['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sal['employee_code']); ?></td>
                                <td><?php echo htmlspecialchars($sal['department'] ?? '-'); ?></td>
                                <td><?php echo formatCurrency($sal['base_salary']); ?></td>
                                <td><?php echo formatCurrency($sal['allowances']); ?></td>
                                <td><?php echo formatCurrency($sal['overtime_amount']); ?></td>
                                <td><?php echo formatCurrency($sal['gross_salary']); ?></td>
                                <td><?php echo formatCurrency($sal['total_deductions']); ?></td>
                                <td><strong><?php echo formatCurrency($sal['net_salary']); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $sal['payment_status']; ?>">
                                        <?php echo ucfirst($sal['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center py-4">No payroll records found. Click "Generate Payroll" to create records.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

