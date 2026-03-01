<?php
/**
 * Employee - My Leave
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/employee_auth.php';
require_once __DIR__ . '/../includes/employee_functions.php';
require_once __DIR__ . '/../includes/logger.php';

requireEmployeeLogin();

$employee = getCurrentEmployee($db);
$employeeId = $employee['id'];

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request';
        $messageType = 'danger';
    } else {
        $result = requestLeave(
            $db,
            $employeeId,
            intval($_POST['leave_type_id']),
            $_POST['start_date'],
            $_POST['end_date'],
            sanitize($_POST['reason'] ?? '')
        );
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }
}

// Get leave types and balances
$leaveTypes = getLeaveTypes($db);
$leaveBalances = getAllLeaveBalances($db, $employeeId, date('Y'));
$leaveRequests = getEmployeeLeaveRequests($db, $employeeId);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'My Leave';
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
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        
        .leave-balance-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
        .leave-balance-item:last-child { border-bottom: none; }
        .leave-name { font-weight: 500; color: var(--dark); }
        .leave-days { font-size: 24px; font-weight: 700; color: var(--primary); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 500px; width: 90%; }
        
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
            <a href="salary.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>My Salary</span></a>
            <a href="leave.php" class="menu-item active"><i class="fas fa-calendar-minus"></i><span>My Leave</span></a>
            <div class="menu-section">Account</div>
<a href="change-password.php" class="menu-item"><i class="fas fa-key"></i><span>Change Password</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-calendar-minus"></i> <?php echo $pageTitle; ?></h1>
            <button class="btn btn-primary" onclick="openModal('requestModal')">
                <i class="fas fa-plus"></i> Request Leave
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Leave Balance -->
        <div class="card">
            <h3><i class="fas fa-calendar-check"></i> Leave Balance (<?php echo date('Y'); ?>)</h3>
            <?php if (count($leaveBalances) > 0): ?>
                <?php foreach ($leaveBalances as $balance): ?>
                    <div class="leave-balance-item">
                        <div>
                            <div class="leave-name"><?php echo htmlspecialchars($balance['leave_type_name']); ?></div>
                            <small class="text-muted"><?php echo $balance['used_days']; ?> used / <?php echo $balance['total_days']; ?> total</small>
                        </div>
                        <div class="leave-days"><?php echo $balance['remaining_days']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No leave records found</p>
            <?php endif; ?>
        </div>

        <!-- Leave Requests -->
        <div class="card">
            <h3><i class="fas fa-list"></i> My Leave Requests</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($leaveRequests) > 0): ?>
                            <?php foreach ($leaveRequests as $leave): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo htmlspecialchars(substr($leave['reason'] ?? '-', 0, 30)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $leave['status']; ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($leave['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No leave requests yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Request Leave Modal -->
    <div class="modal" id="requestModal">
        <div class="modal-content">
            <h3 class="mb-3">Request Leave</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label>Leave Type *</label>
                    <select name="leave_type_id" class="form-control" required>
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leaveTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['leave_type_name']); ?> (<?php echo $type['days_per_year']; ?> days/year)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Start Date *</label>
                            <input type="date" name="start_date" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>End Date *</label>
                            <input type="date" name="end_date" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Enter reason for leave"></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Request</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('requestModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    </script>
</body>
</html>

