<?php
/**
 * Edit Expense
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireAdmin($db);

$error = '';
$success = '';

// Get expense ID
$expenseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$expense = getExpenseById($db, $expenseId);

if (!$expense) {
    redirect('expenses.php', 'Expense not found', 'error');
}

if ($expense['status'] !== 'pending') {
    redirect('expenses.php', 'Only pending expenses can be edited', 'error');
}

// Get categories
$categories = getCategories($db);

// Process update expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = sanitize($_POST['date']);
    $categoryId = (int)$_POST['category_id'];
    $description = sanitize($_POST['description']);
    $invoiceNo = sanitize($_POST['invoice_no'] ?? '');
    $amount = (float)$_POST['amount'];
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($date) || !validateDate($date)) {
        $error = 'Please select a valid date.';
    } elseif (empty($categoryId)) {
        $error = 'Please select a category.';
    } elseif (empty($description)) {
        $error = 'Please enter a description.';
    } elseif ($amount <= 0) {
        $error = 'Please enter a valid amount.';
    } else {
        $db->query("UPDATE expenses SET date = ?, category_id = ?, description = ?, invoice_no = ?, amount = ? WHERE id = ?",
            [$date, $categoryId, $description, $invoiceNo, $amount, $expenseId]);
        
        logExpenseUpdated($db, $expenseId, $expense['voucher_no'], "Amount: $amount, Category: $categoryId");
        
        $success = 'Expense updated successfully!';
        $expense = getExpenseById($db, $expenseId); // Refresh
    }
}

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Edit Expense';
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
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --light: #f8f9fa; --white: #ffffff; --sidebar-width: 260px; }
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
        
        .main-content { margin-left: var(--sidebar-width); padding: 20px; min-height: 100vh; }
        
        .top-header { background: white; padding: 15px 25px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-header h1 { font-size: 22px; color: var(--dark); }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .user-details { text-align: right; }
        .user-details .name { font-size: 14px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 12px; color: #666; }
        
        .form-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 700px; }
        .form-card h3 { font-size: 18px; color: var(--dark); margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s; font-family: inherit; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--secondary); }
        .btn-secondary { background: #6c757d; color: white; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border-left: 4px solid #c33; }
        .alert-success { background: #efe; color: #3c3; border-left: 4px solid #3c3; }
        
        .voucher-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .voucher-info strong { color: var(--primary); }
        
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
            <p>Expense Management ERP</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">Main Menu</div>
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="expenses.php" class="menu-item active"><i class="fas fa-file-invoice-dollar"></i><span>Expenses</span></a>
            <a href="add_expense.php" class="menu-item"><i class="fas fa-plus-circle"></i><span>Add Expense</span></a>
            <a href="opening_balance.php" class="menu-item"><i class="fas fa-wallet"></i><span>Opening Balance</span></a>
            <a href="release_funds.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>Release Funds</span></a>
            <div class="menu-section">Reports</div>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="activity_logs.php" class="menu-item"><i class="fas fa-history"></i><span>Activity Logs</span></a>
            <div class="menu-section">Settings</div>
            <a href="users.php" class="menu-item"><i class="fas fa-users"></i><span>Manage Users</span></a>
            <a href="backup.php" class="menu-item"><i class="fas fa-database"></i><span>Backup</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-edit"></i> <?php echo $pageTitle; ?></h1>
            <div class="user-info">
                <div class="user-details">
                    <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                    <div class="role"><?php echo ucfirst($_SESSION['role_name']); ?></div>
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

        <div class="form-card">
            <div class="voucher-info">
                <strong>Voucher No:</strong> <?php echo htmlspecialchars($expense['voucher_no']); ?>
            </div>
            
            <h3>Update Expense Details</h3>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?php echo $expense['date']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $expense['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="3" required><?php echo htmlspecialchars($expense['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Invoice No (Optional)</label>
                    <input type="text" name="invoice_no" value="<?php echo htmlspecialchars($expense['invoice_no'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Amount (QAR) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="<?php echo $expense['amount']; ?>" required>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Expense
                    </button>
                    <a href="expenses.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

