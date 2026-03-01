<?php
/**
 * Employee Dashboard
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/employee_auth.php';
require_once __DIR__ . '/../includes/employee_functions.php';
require_once __DIR__ . '/../includes/logger.php';

// Require employee login
requireEmployeeLogin();

// Get current employee
$employee = getCurrentEmployee($db);
$employeeId = $employee['id'];

// Get current month/year
$currentYear = date('Y');
$currentMonth = date('m');
$currentMonthName = date('F Y');

// Get attendance summary
$attendanceSummary = getAttendanceSummary($db, $employeeId, $currentYear, $currentMonth);

// Get current month salary
$salary = getEmployeeSalary($db, $employeeId, $currentYear, $currentMonth);

// Get leave balances
$leaveBalances = getAllLeaveBalances($db, $employeeId, $currentYear);

// Get pending leave requests
$pendingLeaves = getEmployeeLeaveRequests($db, $employeeId, 'pending');

// Get recent leave requests
$recentLeaves = getEmployeeLeaveRequests($db, $employeeId);
$recentLeaves = array_slice($recentLeaves, 0, 5);

// Get recent attendance
$recentAttendance = getEmployeeAttendance($db, $employeeId, $currentYear, $currentMonth);
$recentAttendance = array_slice($recentAttendance, 0, 7);

// Get recent salary records
$salaryHistory = getEmployeeSalaries($db, $employeeId, 3);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'My Dashboard';
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
        
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%); color: white; overflow-y: auto; z-index: 1000; transition: transform 0.3s ease; }
        .sidebar.active { transform: translateX(0); }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { width: 70px; height: 70px; background: rgba(255,255.15); border,255,0-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 30px; }
        .sidebar-header h2 { font-size: 16px; font-weight: 600; margin-bottom: 3px; }
        .sidebar-header p { font-size: 11px; opacity: 0.8; }
        .sidebar-menu { padding: 15px 0; }
        .menu-section { padding: 8px 20px; font-size: 11px; text-transform: uppercase; opacity: 0.6; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: white; border-left: 3px solid transparent; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: var(--warning); }
        .menu-item i { width: 25px; font-size: 16px; flex-shrink: 0; }
        .menu-item span { font-size: 14px; margin-left: 10px; }
        .menu-item .badge { margin-left: auto; background: var(--danger); padding: 2px 8px; border-radius: 10px; font-size: 11px; flex-shrink: 0; }
        
        .menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1100; width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; border-radius: 8px; color: white; font-size: 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3); align-items: center; justify-content: center; }
        .menu-toggle.active { left: calc(var(--sidebar-width) + 15px); }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 950; }
        
        .main-content { margin-left: var(--sidebar-width); padding: 25px; min-height: 100vh; }
        
        .top-header { background: white; padding: 15px 25px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-header h1 { font-size: 22px; color: var(--dark); }
        
        .fullscreen-btn { background: none; border: none; font-size: 20px; color: var(--dark); cursor: pointer; padding: 5px 10px; }
        .fullscreen-btn:hover { color: var(--primary); }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .user-details { text-align: right; }
        .user-details .name { font-size: 14px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 12px; color: #666; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
        .card:hover { transform: translateY(-3px); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .card-icon.blue { background: rgba(30, 60, 114, 0.1); color: var(--primary); }
        .card-icon.green { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .card-icon.red { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .card-icon.yellow { background: rgba(255, 193, 7, 0.1); color: #d39e00; }
        .card-icon.orange { background: rgba(253, 126, 20, 0.1); color: #fd7e14; }
        .card-title { font-size: 13px; color: #666; margin-bottom: 8px; }
        .card-amount { font-size: 24px; font-weight: 700; color: var(--dark); }
        
        .info-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .info-card h3 { font-size: 16px; color: var(--dark); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        
        .leave-balance-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee; }
        .leave-balance-item:last-child { border-bottom: none; }
        .leave-info { display: flex; align-items: center; gap: 12px; }
        .leave-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .leave-name { font-weight: 500; color: var(--dark); }
        .leave-days { font-size: 18px; font-weight: 700; color: var(--primary); }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; }
        table td { font-size: 13px; color: var(--dark); }
        table tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-leave { background: #cce5ff; color: #004085; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--secondary); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .profile-section { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; }
        .profile-avatar { width: 80px; height: 80px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: 600; }
        .profile-info h2 { font-size: 20px; color: var(--dark); margin-bottom: 5px; }
        .profile-info p { font-size: 14px; color: #666; margin-bottom: 3px; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: flex; }
            .cards-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 576px) {
            .top-header { flex-direction: column; gap: 15px; }
            .profile-section { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-school"></i>
            </div>
            <h2><?php echo $siteName; ?></h2>
            <p>Employee Portal</p>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-section">Main Menu</div>
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="menu-section">My Information</div>
            <a href="profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="attendance.php" class="menu-item">
                <i class="fas fa-clock"></i>
                <span>My Attendance</span>
            </a>
            <a href="salary.php" class="menu-item">
                <i class="fas fa-money-bill-wave"></i>
                <span>My Salary</span>
            </a>
            <a href="leave.php" class="menu-item">
                <i class="fas fa-calendar-minus"></i>
                <span>My Leave</span>
                <?php if (count($pendingLeaves) > 0): ?>
                    <span class="badge"><?php echo count($pendingLeaves); ?></span>
                <?php endif; ?>
            </a>
            
            <div class="menu-section">Account</div>
            <a href="change-password.php" class="menu-item">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1><i class="fas fa-tachometer-alt"></i> <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <button class="fullscreen-btn" onclick="toggleFullScreen()" title="Toggle Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
                <div class="user-info">
                    <div class="user-details">
                        <div class="name"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                        <div class="role"><?php echo htmlspecialchars($employee['designation'] ?? 'Employee'); ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Profile Summary -->
        <div class="info-card">
            <div class="profile-section">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h2>Welcome, <?php echo htmlspecialchars($employee['full_name']); ?>!</h2>
                    <p><i class="fas fa-id-badge"></i> Employee Code: <strong><?php echo htmlspecialchars($employee['employee_code']); ?></strong></p>
                    <p><i class="fas fa-building"></i> Department: <strong><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></strong> | Designation: <strong><?php echo htmlspecialchars($employee['designation'] ?? 'N/A'); ?></strong></p>
                    <p><i class="fas fa-calendar-plus"></i> Joined: <strong><?php echo formatDate($employee['join_date']); ?></strong></p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="cards-grid">
            <!-- Present Days -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="card-title">Present Days (<?php echo $currentMonthName; ?>)</div>
                <div class="card-amount"><?php echo $attendanceSummary['present_days']; ?></div>
            </div>
            
            <!-- Absent Days -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon red">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="card-title">Absent Days (<?php echo $currentMonthName; ?>)</div>
                <div class="card-amount"><?php echo $attendanceSummary['absent_days']; ?></div>
            </div>
            
            <!-- Late Days -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="card-title">Late Days (<?php echo $currentMonthName; ?>)</div>
                <div class="card-amount"><?php echo $attendanceSummary['late_days']; ?></div>
            </div>
            
            <!-- Current Salary -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon blue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="card-title">Net Salary (<?php echo $currentMonthName; ?>)</div>
                <div class="card-amount"><?php echo $salary ? formatCurrency($salary['net_salary']) : formatCurrency(0); ?></div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="row g-4">
            <!-- Leave Balance -->
            <div class="col-lg-6">
                <div class="info-card">
                    <h3><i class="fas fa-calendar-check"></i> Leave Balance (<?php echo $currentYear; ?>)</h3>
                    <?php if (count($leaveBalances) > 0): ?>
                        <?php foreach ($leaveBalances as $balance): ?>
                            <div class="leave-balance-item">
                                <div class="leave-info">
                                    <div class="leave-icon" style="background: <?php echo $balance['is_paid'] ? 'rgba(40, 167, 69, 0.1)' : 'rgba(108, 117, 125, 0.1)'; ?>; color: <?php echo $balance['is_paid'] ? '#28a745' : '#6c757d'; ?>;">
                                        <i class="fas <?php echo $balance['is_paid'] ? 'fa-check' : 'fa-minus'; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="leave-name"><?php echo htmlspecialchars($balance['leave_type_name']); ?></div>
                                        <small class="text-muted"><?php echo $balance['used_days']; ?> used / <?php echo $balance['total_days']; ?> total</small>
                                    </div>
                                </div>
                                <div class="leave-days"><?php echo $balance['remaining_days']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No leave records found</p>
                    <?php endif; ?>
                    <div class="mt-3 text-end">
                        <a href="leave.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Request Leave</a>
                    </div>
                </div>
            </div>

            <!-- Recent Leave Requests -->
            <div class="col-lg-6">
                <div class="info-card">
                    <h3><i class="fas fa-history"></i> Recent Leave Requests</h3>
                    <?php if (count($recentLeaves) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Dates</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLeaves as $leave): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                        <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                        <td><?php echo $leave['total_days']; ?></td>
                                        <td><span class="status-badge status-<?php echo $leave['status']; ?>"><?php echo ucfirst($leave['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No leave requests yet</p>
                    <?php endif; ?>
                    <div class="mt-3 text-end">
                        <a href="leave.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="info-card">
            <h3><i class="fas fa-calendar-alt"></i> Recent Attendance (<?php echo $currentMonthName; ?>)</h3>
            <?php if (count($recentAttendance) > 0): ?>
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
                            <?php foreach ($recentAttendance as $att): ?>
                            <tr>
                                <td><?php echo formatDate($att['attendance_date']); ?></td>
                                <td><?php echo $att['check_in_time'] ? date('h:i A', strtotime($att['check_in_time'])) : '-'; ?></td>
                                <td><?php echo $att['check_out_time'] ? date('h:i A', strtotime($att['check_out_time'])) : '-'; ?></td>
                                <td><?php echo $att['late_minutes'] > 0 ? $att['late_minutes'] : '-'; ?></td>
                                <td><?php echo $att['overtime_hours'] > 0 ? $att['overtime_hours'] : '-'; ?></td>
                                <td><span class="status-badge status-<?php echo $att['status']; ?>"><?php echo ucfirst($att['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3">No attendance records for this month</p>
            <?php endif; ?>
            <div class="mt-3 text-end">
                <a href="attendance.php" class="btn btn-primary btn-sm">View Full History</a>
            </div>
        </div>

        <!-- Salary History -->
        <div class="info-card">
            <h3><i class="fas fa-file-invoice-dollar"></i> Salary History</h3>
            <?php if (count($salaryHistory) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Base Salary</th>
                                <th>Allowances</th>
                                <th>Overtime</th>
                                <th>Bonus</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salaryHistory as $sal): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($sal['salary_month'])); ?></td>
                                <td><?php echo formatCurrency($sal['base_salary']); ?></td>
                                <td><?php echo formatCurrency($sal['allowances']); ?></td>
                                <td><?php echo formatCurrency($sal['overtime_amount']); ?></td>
                                <td><?php echo formatCurrency($sal['bonus']); ?></td>
                                <td><?php echo formatCurrency($sal['total_deductions']); ?></td>
                                <td><strong><?php echo formatCurrency($sal['net_salary']); ?></strong></td>
                                <td><span class="status-badge status-<?php echo $sal['payment_status']; ?>"><?php echo ucfirst($sal['payment_status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3">No salary records found</p>
            <?php endif; ?>
            <div class="mt-3 text-end">
                <a href="salary.php" class="btn btn-primary btn-sm">View All</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/responsive.js"></script>
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
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

        // Fullscreen toggle
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
    </script>
</body>
</html>

