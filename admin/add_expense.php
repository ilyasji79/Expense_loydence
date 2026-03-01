<?php
/**
 * Add Expense
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

// Get categories
$categories = getCategories($db);

// Process add expense
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
        $voucherNo = generateVoucherNo($db);
        
        $db->query("INSERT INTO expenses (voucher_no, date, category_id, description, invoice_no, amount, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)",
            [$voucherNo, $date, $categoryId, $description, $invoiceNo, $amount, $_SESSION['user_id']]);
        
        $expenseId = $db->lastInsertId();
        logExpenseCreated($db, $expenseId, $voucherNo, $amount);
        
        // Add notification for HR
        $hrUsers = getUsersByRole($db, 2);
        foreach ($hrUsers as $hr) {
            addNotification($db, $hr['id'], 'New Expense Pending', 
                "New expense $voucherNo of $amount QAR is pending your approval.", 
                '../hr/pending_expenses.php');
        }
        
        $success = 'Expense added successfully! Voucher No: ' . $voucherNo;
    }
}

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Add Expense';
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
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --light: #f8f9fa; --white: #ffffff; --sidebar-width: 260px; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; min-height: 100vh; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%); color: white; overflow-y: auto; z-index: 1000; transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0); }
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
        
        .menu-toggle { display: flex; position: fixed; top: 15px; left: 15px; z-index: 1100; width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; border-radius: 8px; color: white; font-size: 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3); align-items: center; justify-content: center; }
        .menu-toggle.active { left: calc(var(--sidebar-width) + 15px); }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 950; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }
        
        .main-content { margin-left: 0; padding: 70px 15px 20px 15px; min-height: 100vh; width: 100%; }
        
        .top-header { background: white; padding: 12px 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 12px; }
        .top-header h1 { font-size: 18px; color: var(--dark); margin-left: 35px; flex: 1; min-width: 0; }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 36px; height: 36px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; flex-shrink: 0; font-size: 14px; }
        .user-details { display: none; text-align: right; }
        .user-details .name { font-size: 13px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 11px; color: #666; }
        
        .form-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 700px; }
        .form-card h3 { font-size: 18px; color: var(--dark); margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group .hint { font-size: 12px; color: #888; margin-top: 5px; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s; font-family: inherit; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--secondary); }
        .btn-secondary { background: #6c757d; color: white; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border-left: 4px solid #c33; }
        .alert-success { background: #efe; color: #3c3; border-left: 4px solid #3c3; }
        
        .info-box { background: #e7f3ff; border: 1px solid #b6d4fe; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .info-box p { color: #084298; font-size: 13px; }
        
        @media (min-width: 481px) {
            .main-content { padding: 75px 20px 20px 20px; }
            .user-details { display: block; }
            .user-details .name { font-size: 14px; }
            .user-details .role { font-size: 12px; }
            .menu-toggle { top: 15px; left: 15px; }
            .menu-toggle.active { left: calc(var(--sidebar-width) + 15px); }
            .top-header { padding: 15px 18px; }
            .top-header h1 { font-size: 20px; margin-left: 40px; }
            .user-avatar { width: 40px; height: 40px; font-size: 16px; }
            .form-card { padding: 25px; }
        }
        
        @media (min-width: 769px) {
            .sidebar { transform: translateX(0); }
            .main-content { margin-left: var(--sidebar-width); padding: 25px; }
            .menu-toggle { display: none; }
            .top-header { padding: 15px 25px; margin-bottom: 25px; }
            .top-header h1 { font-size: 22px; margin-left: 0; }
            .form-card { padding: 30px; }
            .form-card h3 { font-size: 18px; margin-bottom: 25px; padding-bottom: 15px; }
            .form-group { margin-bottom: 20px; }
            .form-group label { font-size: 14px; margin-bottom: 8px; }
            .form-group input, .form-group select, .form-group textarea { padding: 12px 15px; font-size: 14px; }
            .btn { padding: 12px 24px; font-size: 14px; }
        }
    </style>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/responsive.js"></script>
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
            <a href="expenses.php" class="menu-item"><i class="fas fa-file-invoice-dollar"></i><span>Expenses</span></a>
            <a href="add_expense.php" class="menu-item active"><i class="fas fa-plus-circle"></i><span>Add Expense</span></a>
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
            <h1><i class="fas fa-plus-circle"></i> <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-details">
                        <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                        <div class="role"><?php echo ucfirst($_SESSION['role_name']); ?></div>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <a href="add_expense.php" style="margin-left: 10px;">Add Another</a>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <p><i class="fas fa-info-circle"></i> After adding an expense, it will be sent to HR Manager (<?php echo getSetting($db, 'hr_name', HR_NAME); ?>) for approval. Once approved, you can release the funds.</p>
        </div>

        <div class="form-card">
            <h3><i class="fas fa-file-invoice-dollar"></i> New Expense Details</h3>
            <form method="POST" action="" id="expenseForm">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="3" placeholder="Enter expense description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Invoice No (Optional)</label>
                    <input type="text" name="invoice_no" placeholder="Enter invoice number">
                </div>
                
                <div class="form-group">
                    <label>Amount (QAR) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
                    <div class="hint">Enter amount in QAR (Qatar Riyal)</div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Expense
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

