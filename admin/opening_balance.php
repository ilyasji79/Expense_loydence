<?php
/**
 * Opening Balance Management
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

// Process add opening balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $amount = (float)($_POST['amount'] ?? 0);
    $balanceDate = sanitize($_POST['balance_date']);
    $description = sanitize($_POST['description'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif ($amount <= 0) {
        $error = 'Please enter a valid amount.';
    } elseif (!validateDate($balanceDate)) {
        $error = 'Please select a valid date.';
    } else {
        $db->query("INSERT INTO opening_balance (amount, balance_date, description, added_by) VALUES (?, ?, ?, ?)",
            [$amount, $balanceDate, $description, $_SESSION['user_id']]);
        
        logOpeningBalanceUpdate($db, $amount);
        $success = 'Opening balance added successfully!';
    }
}

// Get current opening balance
$currentBalance = getOpeningBalance($db);
$balanceHistory = $db->fetchAll("SELECT ob.*, u.full_name as added_by_name 
    FROM opening_balance ob 
    LEFT JOIN users u ON ob.added_by = u.id 
    ORDER BY ob.balance_date DESC");

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Opening Balance';
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
            <a href="add_expense.php" class="menu-item"><i class="fas fa-plus-circle"></i><span>Add Expense</span></a>
            <a href="opening_balance.php" class="menu-item active"><i class="fas fa-wallet"></i><span>Opening Balance</span></a>
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
        <div class="page-container">
            <div class="top-header">
                <h1><i class="fas fa-wallet"></i> <?php echo $pageTitle; ?></h1>
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

            <!-- Current Balance -->
            <div class="balance-card">
                <h2>Current Opening Balance</h2>
                <div class="amount"><?php echo number_format($currentBalance, 2); ?> QAR</div>
                <div class="subtitle">Total funds available for expenses</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add Balance Form -->
            <div class="form-card">
                <h3><i class="fas fa-plus-circle"></i> Add Opening Balance</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    
                    <div class="form-group">
                        <label>Amount (QAR)</label>
                        <input type="number" name="amount" step="0.01" min="0" placeholder="Enter amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="balance_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <textarea name="description" rows="2" placeholder="Enter description"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Balance
                    </button>
                </form>
            </div>

            <!-- Balance History -->
            <div class="table-card">
                <h3><i class="fas fa-history"></i> Balance History</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Added By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($balanceHistory) > 0): ?>
                                <?php foreach ($balanceHistory as $balance): ?>
                                <tr>
                                    <td><?php echo formatDate($balance['balance_date']); ?></td>
                                    <td><strong><?php echo number_format($balance['amount'], 2); ?> QAR</strong></td>
                                    <td><?php echo htmlspecialchars($balance['description'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($balance['added_by_name'] ?? 'Unknown'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 30px;">No balance history found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

