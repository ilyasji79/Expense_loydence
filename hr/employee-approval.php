<?php
/**
 * HR - Employee Approval
 * Expense Management ERP - Loydence Academy
 * 
 * HR/Admin can approve pending employee user accounts
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
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
        $userId = intval($_POST['user_id']);
        
        switch ($_POST['action']) {
            case 'approve':
                $db->query("UPDATE users SET status = 'approved' WHERE id = ?", [$userId]);
                logActivity($db, $_SESSION['user_id'], 'employee_approved', "Employee user account approved. User ID: $userId");
                $message = 'Employee account approved successfully!';
                $messageType = 'success';
                break;
                
            case 'reject':
                $reason = sanitize($_POST['rejection_reason'] ?? '');
                $db->query("UPDATE users SET status = 'rejected' WHERE id = ?", [$userId]);
                logActivity($db, $_SESSION['user_id'], 'employee_rejected', "Employee user account rejected. User ID: $userId. Reason: $reason");
                $message = 'Employee account rejected.';
                $messageType = 'warning';
                break;
        }
    }
}

// Get pending employees
$pendingEmployees = $db->fetchAll("
    SELECT u.*, e.full_name, e.employee_code, e.department, e.designation
    FROM users u
    LEFT JOIN employees e ON u.id = e.user_id
    WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'employee')
    AND u.status = 'pending'
    ORDER BY u.created_at DESC
");

// Get counts
$pendingCount = count($pendingEmployees);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Employee Approval';
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
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 400px; width: 90%; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        
        .action-btns { display: flex; gap: 5px; }
        
        .empty-state { text-align: center; padding: 50px 20px; }
        .empty-state i { font-size: 48px; color: #28a745; margin-bottom: 20px; }
        
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
            <a href="employee-approval.php" class="menu-item active"><i class="fas fa-user-check"></i><span>Employee Approval</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="attendance.php" class="menu-item"><i class="fas fa-clock"></i><span>Attendance</span></a>
            <a href="payroll.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>Payroll</span></a>
            <a href="leave_requests.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>Leave Requests</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-user-check"></i> <?php echo $pageTitle; ?></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($pendingCount > 0): ?>
        <div class="card">
            <h3><i class="fas fa-user-plus"></i> Pending Employee Approvals (<?php echo $pendingCount; ?>)</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingEmployees as $emp): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['full_name'] ?? 'N/A'); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($emp['employee_code'] ?? 'No code'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($emp['username']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($emp['designation'] ?? 'N/A'); ?></td>
                            <td><?php echo formatDate($emp['created_at']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?php echo $emp['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <button class="btn btn-danger btn-sm" onclick="rejectEmployee(<?php echo $emp['id']; ?>)" title="Reject">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>No Pending Approvals</h3>
                <p class="text-muted">All employee accounts are already approved.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <h3 class="mb-3">Reject Employee Account</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="user_id" id="reject_user_id">
                
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
        
        function rejectEmployee(id) {
            document.getElementById('reject_user_id').value = id;
            openModal('rejectModal');
        }
    </script>
</body>
</html>

