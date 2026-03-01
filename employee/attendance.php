<?php
/**
 * Employee - My Attendance
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

// Get filters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// Get attendance records
$attendanceRecords = getEmployeeAttendance($db, $employeeId, $year, $month);
$attendanceSummary = getAttendanceSummary($db, $employeeId, $year, $month);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'My Attendance';
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
        
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .summary-card .count { font-size: 32px; font-weight: 700; color: var(--primary); }
        .summary-card .label { font-size: 13px; color: #666; }
        .summary-card.success .count { color: #28a745; }
        .summary-card.danger .count { color: #dc3545; }
        .summary-card.warning .count { color: #ffc107; }
        .summary-card.info .count { color: #17a2b8; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-leave { background: #cce5ff; color: #004085; }
        .status-holiday { background: #e2e3e5; color: #383d41; }
        
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .form-control { padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
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
            <a href="attendance.php" class="menu-item active"><i class="fas fa-clock"></i><span>My Attendance</span></a>
            <a href="salary.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>My Salary</span></a>
            <a href="leave.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>My Leave</span></a>
            <div class="menu-section">Account</div>
<a href="change-password.php" class="menu-item"><i class="fas fa-key"></i><span>Change Password</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-clock"></i> <?php echo $pageTitle; ?></h1>
        </div>

        <!-- Filters -->
        <div class="card">
            <form method="GET" class="filter-bar">
                <select name="year" class="form-control" style="width: auto;">
                    <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <select name="month" class="form-control" style="width: auto;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m)); ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        <!-- Summary -->
        <div class="summary-cards">
            <div class="summary-card success">
                <div class="count"><?php echo $attendanceSummary['present_days']; ?></div>
                <div class="label">Present</div>
            </div>
            <div class="summary-card danger">
                <div class="count"><?php echo $attendanceSummary['absent_days']; ?></div>
                <div class="label">Absent</div>
            </div>
            <div class="summary-card warning">
                <div class="count"><?php echo $attendanceSummary['late_days']; ?></div>
                <div class="label">Late</div>
            </div>
            <div class="summary-card info">
                <div class="count"><?php echo $attendanceSummary['leave_days']; ?></div>
                <div class="label">Leave</div>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="card">
            <h3>Attendance Details - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Late (min)</th>
                            <th>Overtime (hrs)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendanceRecords) > 0): ?>
                            <?php foreach ($attendanceRecords as $att): ?>
                            <tr>
                                <td><?php echo formatDate($att['attendance_date']); ?></td>
                                <td><?php echo $att['check_in_time'] ? date('h:i A', strtotime($att['check_in_time'])) : '-'; ?></td>
                                <td><?php echo $att['check_out_time'] ? date('h:i A', strtotime($att['check_out_time'])) : '-'; ?></td>
                                <td><?php echo $att['late_minutes'] > 0 ? $att['late_minutes'] : '-'; ?></td>
                                <td><?php echo $att['overtime_hours'] > 0 ? $att['overtime_hours'] : '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $att['status']; ?>">
                                        <?php echo ucfirst($att['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4">No attendance records found for this month</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

