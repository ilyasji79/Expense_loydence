<?php
/**
 * HR - Attendance Management
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
            case 'mark_attendance':
                $result = recordManualAttendance(
                    $db,
                    intval($_POST['employee_id']),
                    $_POST['attendance_date'],
                    $_POST['check_in_time'],
                    $_POST['check_out_time'] ?? null,
                    $_POST['status'],
                    sanitize($_POST['notes'] ?? '')
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'import_attendance':
                // Handle bulk import
                $message = 'Attendance import feature coming soon';
                $messageType = 'info';
                break;
        }
    }
}

// Get filters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$department = isset($_GET['department']) ? $_GET['department'] : '';
$employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

// Get employees
$employees = getAllEmployees($db);
$departments = getDepartments($db);

// Get attendance report
$attendanceData = getAttendanceReport($db, $year, $month, $department ?: null);

// Get pending leaves for the month
$pendingLeaves = getAllLeaveRequests($db, 'pending');

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Attendance Management';
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
        .filter-bar .form-group { margin-bottom: 0; }
        
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 15px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .summary-card .count { font-size: 28px; font-weight: 700; color: var(--primary); }
        .summary-card .label { font-size: 12px; color: #666; }
        
        .status-present { color: #28a745; }
        .status-absent { color: #dc3545; }
        .status-late { color: #ffc107; }
        .status-leave { color: #17a2b8; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 500px; width: 90%; }
        
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
            <a href="attendance.php" class="menu-item active"><i class="fas fa-clock"></i><span>Attendance</span></a>
            <a href="payroll.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>Payroll</span></a>
            <a href="leave_requests.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>Leave Requests</span></a>
            <a href="employee_reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-clock"></i> <?php echo $pageTitle; ?></h1>
            <button class="btn btn-primary" onclick="openModal('attendanceModal')">
                <i class="fas fa-plus"></i> Mark Attendance
            </button>
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
                <div class="form-group">
                    <label>Department</label>
                    <select name="department" class="form-control">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
<option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $dept['department'] == $department ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <a href="attendance.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <!-- Summary -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="count"><?php echo array_sum(array_column($attendanceData, 'present_days')); ?></div>
                <div class="label">Present Days</div>
            </div>
            <div class="summary-card">
                <div class="count"><?php echo array_sum(array_column($attendanceData, 'absent_days')); ?></div>
                <div class="label">Absent Days</div>
            </div>
            <div class="summary-card">
                <div class="count"><?php echo array_sum(array_column($attendanceData, 'late_days')); ?></div>
                <div class="label">Late Days</div>
            </div>
            <div class="summary-card">
                <div class="count"><?php echo count($employees); ?></div>
                <div class="label">Total Employees</div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card">
            <h3>Attendance Report - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Leave</th>
                            <th>Late (min)</th>
                            <th>OT (hrs)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendanceData) > 0): ?>
                            <?php foreach ($attendanceData as $att): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($att['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($att['employee_code']); ?></td>
                                <td><?php echo htmlspecialchars($att['department'] ?? '-'); ?></td>
                                <td class="status-present"><?php echo $att['present_days']; ?></td>
                                <td class="status-absent"><?php echo $att['absent_days']; ?></td>
                                <td class="status-late"><?php echo $att['late_days']; ?></td>
                                <td class="status-leave"><?php echo $att['leave_days']; ?></td>
                                <td><?php echo $att['total_late_minutes']; ?></td>
                                <td><?php echo $att['total_overtime_hours']; ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="viewDetails(<?php echo $att['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center py-4">No attendance records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mark Attendance Modal -->
    <div class="modal" id="attendanceModal">
        <div class="modal-content">
            <h3 class="mb-3">Mark Attendance</h3>
            <form method="POST">
                <input type="hidden" name="action" value="mark_attendance">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name'] . ' - ' . $emp['employee_code']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Check In</label>
                            <input type="time" name="check_in_time" class="form-control" value="08:00:00">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Check Out</label>
                            <input type="time" name="check_out_time" class="form-control" value="16:00:00">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                        <option value="leave">Leave</option>
                        <option value="holiday">Holiday</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('attendanceModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        function viewDetails(id) { alert('View details for employee ID: ' + id); }
    </script>
</body>
</html>

