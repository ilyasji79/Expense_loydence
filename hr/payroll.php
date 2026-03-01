<?php
/**
 * HR - Payroll Management
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/employee_functions.php';
require_once __DIR__ . '/../includes/logger.php';

requireLogin();
if (!hasRole($db, 'admin') && !hasRole($db, 'hr_manager')) {
    redirect(BASE_URL . '/dashboard.php', 'Access denied', 'error');
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request';
        $messageType = 'danger';
    } else {
        switch ($_POST['action']) {
            case 'generate_salary':
                $result = generateSalary($db, intval($_POST['employee_id']), intval($_POST['year']), intval($_POST['month']));
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'generate_all':
                $employees = getAllEmployees($db);
                $generated = 0;
                $failed = 0;
                foreach ($employees as $emp) {
                    $result = generateSalary($db, $emp['id'], intval($_POST['year']), intval($_POST['month']));
                    if ($result['success']) $generated++;
                    else $failed++;
                }
                $message = "Salary generation completed: $generated successful, $failed failed";
                $messageType = $generated > 0 ? 'success' : 'danger';
                break;
                
            case 'update_payment':
                $result = updateSalaryPayment(
                    $db,
                    intval($_POST['salary_id']),
                    $_POST['payment_type'],
                    $_POST['payment_status'],
                    $_POST['payment_date'] ?? date('Y-m-d'),
                    sanitize($_POST['payment_reference'] ?? '')
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
        }
    }
}

// Get filters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Get employees
$employees = getAllEmployees($db);
$departments = getDepartments($db);

// Get payroll report
$payrollData = getPayrollReport($db, $year, $month);

// Calculate totals
$totalBase = 0;
$totalGross = 0;
$totalDeductions = 0;
$totalNet = 0;
$totalPaid = 0;

foreach ($payrollData as $p) {
    $totalBase += $p['base_salary'];
    $totalGross += $p['gross_salary'];
    $totalDeductions += $p['total_deductions'];
    $totalNet += $p['net_salary'];
    if ($p['payment_status'] === 'paid') $totalPaid += $p['net_salary'];
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
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: var(--dark); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        table td { font-size: 13px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        
        .filter-bar { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; align-items: end; }
        
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .summary-card .label { font-size: 12px; color: #666; margin-bottom: 5px; }
        .summary-card .amount { font-size: 24px; font-weight: 700; color: var(--primary); }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processed { background: #cce5ff; color: #004085; }
        .status-paid { background: #d4edda; color: #155724; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        
        .action-btns { display: flex; gap: 5px; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .filter-bar { flex-direction: column; }
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
            <div class="menu-section">Employee Management</div>
            <a href="employees.php" class="menu-item"><i class="fas fa-users"></i><span>Employees</span></a>
            <a href="attendance.php" class="menu-item"><i class="fas fa-clock"></i><span>Attendance</span></a>
            <a href="payroll.php" class="menu-item active"><i class="fas fa-money-bill-wave"></i><span>Payroll</span></a>
            <a href="leave_requests.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>Leave Requests</span></a>
<a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-money-bill-wave"></i> <?php echo $pageTitle; ?></h1>
            <div>
                <button class="btn btn-primary" onclick="openModal('generateModal')">
                    <i class="fas fa-calculator"></i> Generate Salary
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card">
            <form method="GET" class="filter-bar">
                <div class="form-group">
                    <label>Year</label>
                    <select name="year" class="form-control">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Month</label>
                    <select name="month" class="form-control">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <a href="payroll.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="label">Total Employees</div>
                <div class="amount"><?php echo count($payrollData); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Total Base Salary</div>
                <div class="amount"><?php echo formatCurrency($totalBase); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Total Gross Salary</div>
                <div class="amount"><?php echo formatCurrency($totalGross); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Total Deductions</div>
                <div class="amount"><?php echo formatCurrency($totalDeductions); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Total Net Salary</div>
                <div class="amount"><?php echo formatCurrency($totalNet); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Total Paid</div>
                <div class="amount"><?php echo formatCurrency($totalPaid); ?></div>
            </div>
        </div>

        <!-- Payroll Table -->
        <div class="card">
            <h3>Salary Details - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Base</th>
                            <th>Allowances</th>
                            <th>Overtime</th>
                            <th>Bonus</th>
                            <th>Gross</th>
                            <th>Tax</th>
                            <th>Other Ded.</th>
                            <th>Net</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payrollData) > 0): ?>
                            <?php foreach ($payrollData as $sal): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sal['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($sal['employee_code']); ?></td>
                                <td><?php echo htmlspecialchars($sal['department'] ?? '-'); ?></td>
                                <td><?php echo formatCurrency($sal['base_salary']); ?></td>
                                <td><?php echo formatCurrency($sal['allowances']); ?></td>
                                <td><?php echo formatCurrency($sal['overtime_amount']); ?></td>
                                <td><?php echo formatCurrency($sal['bonus']); ?></td>
                                <td><strong><?php echo formatCurrency($sal['gross_salary']); ?></strong></td>
                                <td><?php echo formatCurrency($sal['tax_deduction']); ?></td>
                                <td><?php echo formatCurrency($sal['other_deductions']); ?></td>
                                <td><strong><?php echo formatCurrency($sal['net_salary']); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $sal['payment_status']; ?>">
                                        <?php echo ucfirst($sal['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn btn-primary btn-sm" onclick="viewSlip(<?php echo $sal['id']; ?>)" title="View Slip">
                                            <i class="fas fa-file-invoice"></i>
                                        </button>
                                        <button class="btn btn-success btn-sm" onclick="updatePayment(<?php echo $sal['id']; ?>, '<?php echo $sal['payment_status']; ?>', '<?php echo $sal['payment_type']; ?>')" title="Update Payment">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="13" class="text-center py-4">No salary records found. Generate salaries first.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Generate Salary Modal -->
    <div class="modal" id="generateModal">
        <div class="modal-content">
            <h3 class="mb-3">Generate Salary</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label>Generate For</label>
                    <select name="action" class="form-control" onchange="toggleEmployeeSelect(this.value)">
                        <option value="generate_all">All Employees</option>
                        <option value="generate_salary">Specific Employee</option>
                    </select>
                </div>
                
                <div class="form-group" id="employeeSelect" style="display: none;">
                    <label>Employee</label>
                    <select name="employee_id" class="form-control">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Year</label>
                            <select name="year" class="form-control">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Month</label>
                            <select name="month" class="form-control">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-calculator"></i> Generate</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('generateModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Payment Modal -->
    <div class="modal" id="paymentModal">
        <div class="modal-content">
            <h3 class="mb-3">Update Payment</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="salary_id" id="pay_salary_id">
                
                <div class="form-group">
                    <label>Payment Type</label>
                    <select name="payment_type" class="form-control" id="pay_type">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status" class="form-control" id="pay_status">
                        <option value="pending">Pending</option>
                        <option value="processed">Processed</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Reference No.</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="Transaction reference">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        function toggleEmployeeSelect(value) {
            document.getElementById('employeeSelect').style.display = value === 'generate_salary' ? 'block' : 'none';
        }
        
        function viewSlip(id) {
            window.open('salary_slip.php?id=' + id, '_blank');
        }
        
        function updatePayment(id, status, type) {
            document.getElementById('pay_salary_id').value = id;
            document.getElementById('pay_status').value = status;
            document.getElementById('pay_type').value = type || 'bank_transfer';
            openModal('paymentModal');
        }
    </script>
</body>
</html>

