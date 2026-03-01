<?php
/**
 * Employee - My Salary
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/employee_auth.php';
require_once __DIR__ . '/../includes/employee_functions.php';

requireEmployeeLogin();

$employee = getCurrentEmployee($db);
$employeeId = $employee['id'];

// Get salary history
$salaries = getEmployeeSalaries($db, $employeeId, 12);

// Get current month salary
$currentSalary = getEmployeeSalary($db, $employeeId, date('Y'), date('m'));

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'My Salary';
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
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --dark: #343a40; --sidebar-width: 260px; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; min-height: 100vh; }
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
        
        .salary-card { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .salary-item { padding: 15px; border-radius: 8px; text-align: center; }
        .salary-item .label { font-size: 12px; color: #666; margin-bottom: 5px; }
        .salary-item .amount { font-size: 20px; font-weight: 700; color: var(--dark); }
        .salary-item.highlight { background: rgba(30, 60, 114, 0.1); }
        .salary-item.highlight .amount { color: var(--primary); }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processed { background: #cce5ff; color: #004085; }
        .status-paid { background: #d4edda; color: #155724; }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; }
        .btn-primary { background: var(--primary); color: white; }
        
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
            <p>Employee Portal</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">Main Menu</div>
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="profile.php" class="menu-item"><i class="fas fa-user"></i><span>My Profile</span></a>
            <a href="attendance.php" class="menu-item"><i class="fas fa-clock"></i><span>My Attendance</span></a>
            <a href="salary.php" class="menu-item active"><i class="fas fa-money-bill-wave"></i><span>My Salary</span></a>
            <a href="leave.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>My Leave</span></a>
            <div class="menu-section">Account</div>
<a href="change-password.php" class="menu-item"><i class="fas fa-key"></i><span>Change Password</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-money-bill-wave"></i> <?php echo $pageTitle; ?></h1>
        </div>

        <!-- Current Month Salary -->
        <?php if ($currentSalary): ?>
        <div class="card">
            <h3><i class="fas fa-file-invoice-dollar"></i> Current Month Salary (<?php echo date('F Y'); ?>)</h3>
            <div class="salary-card">
                <div class="salary-item">
                    <div class="label">Base Salary</div>
                    <div class="amount"><?php echo formatCurrency($currentSalary['base_salary']); ?></div>
                </div>
                <div class="salary-item">
                    <div class="label">Allowances</div>
                    <div class="amount"><?php echo formatCurrency($currentSalary['allowances']); ?></div>
                </div>
                <div class="salary-item">
                    <div class="label">Overtime</div>
                    <div class="amount"><?php echo formatCurrency($currentSalary['overtime_amount']); ?></div>
                </div>
                <div class="salary-item">
                    <div class="label">Bonus</div>
                    <div class="amount"><?php echo formatCurrency($currentSalary['bonus']); ?></div>
                </div>
                <div class="salary-item">
                    <div class="label">Gross Salary</div>
                    <div class="amount"><?php echo formatCurrency($currentSalary['gross_salary']); ?></div>
                </div>
                <div class="salary-item">
                    <div class="label">Tax Deduction</div>
                    <div class="amount">-<?php echo formatCurrency($currentSalary['tax_deduction']); ?></div>
                </div>
                <div class="salary-item">
                    <div class="label">Other Deductions</div>
                    <div class="amount">-<?php echo formatCurrency($currentSalary['other_deductions']); ?></div>
                </div>
                <div class="salary-item highlight">
                    <div class="label">Net Salary</div>
                    <div class="amount"><?php echo formatCurrency($currentSalary['net_salary']); ?></div>
                </div>
            </div>
            <div class="mt-3">
                <span class="status-badge status-<?php echo $currentSalary['payment_status']; ?>">
                    Payment: <?php echo ucfirst($currentSalary['payment_status']); ?>
                </span>
                <?php if ($currentSalary['payment_date']): ?>
                    <span class="ms-2">Paid on: <?php echo formatDate($currentSalary['payment_date']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Salary History -->
        <div class="card">
            <h3><i class="fas fa-history"></i> Salary History</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Base Salary</th>
                            <th>Allowances</th>
                            <th>Overtime</th>
                            <th>Bonus</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($salaries) > 0): ?>
                            <?php foreach ($salaries as $sal): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($sal['salary_month'])); ?></td>
                                <td><?php echo formatCurrency($sal['base_salary']); ?></td>
                                <td><?php echo formatCurrency($sal['allowances']); ?></td>
                                <td><?php echo formatCurrency($sal['overtime_amount']); ?></td>
                                <td><?php echo formatCurrency($sal['bonus']); ?></td>
                                <td><strong><?php echo formatCurrency($sal['gross_salary']); ?></strong></td>
                                <td>-<?php echo formatCurrency($sal['total_deductions']); ?></td>
                                <td><strong><?php echo formatCurrency($sal['net_salary']); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $sal['payment_status']; ?>">
                                        <?php echo ucfirst($sal['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4">No salary records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

