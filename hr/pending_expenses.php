<?php
/**
 * Pending Expenses - HR Approval
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireHR($db);

$error = '';
$success = '';

// Process approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expenseId = (int)$_POST['expense_id'];
    $action = $_POST['action'];
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid request.';
    } else {
        $expense = getExpenseById($db, $expenseId);
        
        if (!$expense) {
            $error = 'Expense not found.';
        } elseif ($expense['status'] !== 'pending') {
            $error = 'This expense has already been processed.';
        } else {
            if ($action === 'approve') {
                $db->query("UPDATE expenses SET status = 'approved', hr_approved_by = ?, hr_approval_date = NOW() WHERE id = ?",
                    [$_SESSION['user_id'], $expenseId]);
                
                logApproval($db, $expenseId, $expense['voucher_no'], $expense['amount'], true);
                
                // Log in approvals table
                $db->query("INSERT INTO approvals (expense_id, approved_by, approval_status, approval_date) VALUES (?, ?, 'approved', NOW())",
                    [$expenseId, $_SESSION['user_id']]);
                
                // Notify admin
                $adminUsers = getUsersByRole($db, 1);
                foreach ($adminUsers as $admin) {
                    addNotification($db, $admin['id'], 'Expense Approved', 
                        "Expense {$expense['voucher_no']} has been approved by HR. Ready for fund release.", 
                        '../admin/release_funds.php');
                }
                
                $success = 'Expense approved successfully!';
                
            } elseif ($action === 'reject') {
                $reason = sanitize($_POST['rejection_reason'] ?? '');
                
                if (empty($reason)) {
                    $error = 'Please provide a reason for rejection.';
                } else {
                    $db->query("UPDATE expenses SET status = 'rejected', hr_approved_by = ?, hr_approval_date = NOW(), rejection_reason = ? WHERE id = ?",
                        [$_SESSION['user_id'], $reason, $expenseId]);
                    
                    logApproval($db, $expenseId, $expense['voucher_no'], $expense['amount'], false);
                    
                    // Log in approvals table
                    $db->query("INSERT INTO approvals (expense_id, approved_by, approval_status, approval_date, notes) VALUES (?, ?, 'rejected', NOW(), ?)",
                        [$expenseId, $_SESSION['user_id'], $reason]);
                    
                    // Notify admin
                    $adminUsers = getUsersByRole($db, 1);
                    foreach ($adminUsers as $admin) {
                        addNotification($db, $admin['id'], 'Expense Rejected', 
                            "Expense {$expense['voucher_no']} has been rejected by HR. Reason: $reason", 
                            '../admin/expenses.php');
                    }
                    
                    $success = 'Expense rejected.';
                }
            }
        }
    }
}

$pendingExpenses = getExpensesByStatus($db, 'pending');

$siteName = getSetting($db, 'school_name', SITE_NAME);
$hrName = getSetting($db, 'hr_name', HR_NAME);
$pageTitle = 'Pending Approvals';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $siteName; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .user-details { text-align: right; }
        .user-details .name { font-size: 14px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 12px; color: #666; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border-left: 4px solid #c33; }
        .alert-success { background: #efe; color: #3c3; border-left: 4px solid #3c3; }
        
        .table-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .table-card h3 { font-size: 16px; color: var(--dark); margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        table td { font-size: 13px; color: var(--dark); }
        
        .btn { padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; border: none; transition: all 0.3s; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #c82333; }
        
        .action-btns { display: flex; gap: 8px; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 25px; border-radius: 12px; max-width: 500px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { color: var(--danger); }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .table-card { overflow-x: auto; }
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
            <a href="pending_expenses.php" class="menu-item active">
                <i class="fas fa-clipboard-check"></i><span>Pending Approvals</span>
                <?php if (count($pendingExpenses) > 0): ?><span class="badge"><?php echo count($pendingExpenses); ?></span><?php endif; ?>
            </a>
            <a href="approved_expenses.php" class="menu-item"><i class="fas fa-check-circle"></i><span>Approved</span></a>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-clipboard-check"></i> <?php echo $pageTitle; ?></h1>
            <div class="user-info">
                <div class="user-details">
                    <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                    <div class="role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role_name'])); ?></div>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="table-card">
            <h3><i class="fas fa-clock"></i> Expenses Pending Your Approval</h3>
            <?php if (count($pendingExpenses) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Voucher No</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Invoice No</th>
                        <th>Amount</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingExpenses as $expense): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($expense['voucher_no']); ?></strong></td>
                        <td><?php echo formatDate($expense['date']); ?></td>
                        <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($expense['description'], 0, 40)); ?>...</td>
                        <td><?php echo htmlspecialchars($expense['invoice_no'] ?? '-'); ?></td>
                        <td><strong><?php echo number_format($expense['amount'], 2); ?> QAR</strong></td>
                        <td><?php echo htmlspecialchars($expense['creator_name']); ?></td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Approve this expense?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <button class="btn btn-danger" onclick="openRejectModal(<?php echo $expense['id']; ?>, '<?php echo htmlspecialchars($expense['voucher_no']); ?>')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 15px; display: block; color: var(--success); opacity: 0.5;"></i>
                No pending expenses to approve
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle"></i> Reject Expense</h3>
                <button class="modal-close" onclick="closeRejectModal()">&times;</button>
            </div>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="expense_id" id="rejectExpenseId">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label>Voucher No</label>
                    <p id="rejectVoucherNo" style="font-weight: 600; color: var(--primary);"></p>
                </div>
                
                <div class="form-group">
                    <label>Rejection Reason *</label>
                    <textarea name="rejection_reason" rows="4" placeholder="Please provide a reason for rejection" required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: #6c757d; color: white;" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id, voucherNo) {
            document.getElementById('rejectExpenseId').value = id;
            document.getElementById('rejectVoucherNo').textContent = voucherNo;
            document.getElementById('rejectModal').classList.add('active');
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }
    </script>
</body>
</html>

