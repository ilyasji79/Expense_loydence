<?php
/**
 * HR - Leave Requests Management
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
            case 'approve':
                $result = processLeaveRequest($db, intval($_POST['leave_id']), 'approved');
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'reject':
                $result = processLeaveRequest($db, intval($_POST['leave_id']), 'rejected', sanitize($_POST['rejection_reason'] ?? ''));
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
        }
    }
}

// Get filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get leave requests
$leaveRequests = getAllLeaveRequests($db, $status ?: null);

// Get counts
$pendingCount = getPendingLeaveCount($db);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Leave Requests';
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
        .menu-item .badge { margin-left: auto; background: var(--danger); padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        
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
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tabs .btn { background: #f8f9fa; color: var(--dark); }
        .filter-tabs .btn.active { background: var(--primary); color: white; }
        
        .action-btns { display: flex; gap: 5px; }
        
        .modal { display: none; position: fixed; top: 0;; width:  left: 0100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 400px; width: 90%; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        
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
            <a href="payroll.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>Payroll</span></a>
            <a href="leave_requests.php" class="menu-item active"><i class="fas fa-calendar-minus"></i><span>Leave Requests</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="employee_reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-calendar-minus"></i> <?php echo $pageTitle; ?></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="leave_requests.php" class="btn <?php echo $status === '' ? 'active' : ''; ?>">All</a>
            <a href="leave_requests.php?status=pending" class="btn <?php echo $status === 'pending' ? 'active' : ''; ?>">Pending
                <?php if ($pendingCount > 0): ?>
                    <span class="badge"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="leave_requests.php?status=approved" class="btn <?php echo $status === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="leave_requests.php?status=rejected" class="btn <?php echo $status === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>

        <!-- Leave Requests Table -->
        <div class="card">
            <h3>Leave Requests (<?php echo count($leaveRequests); ?>)</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($leaveRequests) > 0): ?>
                            <?php foreach ($leaveRequests as $leave): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($leave['employee_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($leave['employee_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo htmlspecialchars(substr($leave['reason'] ?? '-', 0, 50)); ?>...</td>
                                <td>
                                    <span class="status-badge status-<?php echo $leave['status']; ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($leave['created_at']); ?></td>
                                <td>
                                    <?php if ($leave['status'] === 'pending'): ?>
                                        <div class="action-btns">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <button class="btn btn-danger btn-sm" onclick="rejectLeave(<?php echo $leave['id']; ?>)" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">
                                            <?php if ($leave['approver_name']): ?>
                                                By <?php echo htmlspecialchars($leave['approver_name']); ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4">No leave requests found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <h3 class="mb-3">Reject Leave Request</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="leave_id" id="reject_leave_id">
                
                <div class="form-group">
                    <label>Rejection Reason</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Enter reason for rejection"></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        function rejectLeave(id) {
            document.getElementById('reject_leave_id').value = id;
            openModal('rejectModal');
        }
    </script>
</body>
</html>

