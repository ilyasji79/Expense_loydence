<?php
/**
 * Database Backup
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

// Process backup
if (isset($_POST['action']) && $_POST['action'] === 'backup') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid request.';
    } else {
        try {
            // Get database name
            $dbName = DB_NAME;
            
            // Create backup directory if not exists
            $backupDir = __DIR__ . '/../backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Generate filename
            $filename = 'expense_erp_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;
            
            // Get all tables
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $sqlContent = "-- Database Backup\n-- School: " . getSetting($db, 'school_name', SITE_NAME) . "\n-- Date: " . date('Y-m-d H:i:s') . "\n-- \n\n";
            $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $sqlContent .= "\n-- Table: $table\n";
                
                // Get create table syntax
                $createResult = $db->query("SHOW CREATE TABLE $table");
                $createRow = $createResult->fetch();
                $sqlContent .= "DROP TABLE IF EXISTS `$table`;\n";
                $sqlContent .= $createRow[1] . ";\n\n";
                
                // Get table data
                $dataResult = $db->query("SELECT * FROM $table");
                while ($data = $dataResult->fetch(PDO::FETCH_ASSOC)) {
                    $values = [];
                    foreach ($data as $value) {
                        $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }
                    $sqlContent .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
            }
            
            $sqlContent .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
            
            // Write to file
            file_put_contents($filepath, $sqlContent);
            
            logBackup($db, $filename);
            $success = 'Database backup created successfully: ' . $filename;
            
        } catch (Exception $e) {
            $error = 'Backup failed: ' . $e->getMessage();
        }
    }
}

// Get backup files
$backupDir = __DIR__ . '/../backups';
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backupDir . '/' . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => filemtime($filepath)
            ];
        }
    }
    usort($backups, function($a, $b) { return $b['date'] - $a['date']; });
}

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Database Backup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $siteName; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/responsive.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --dark: #343a40; --sidebar-width: 260px; }
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
        
        .btn { padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border-left: 4px solid #c33; }
        .alert-success { background: #efe; color: #3c3; border-left: 4px solid #3c3; }
        
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { font-size: 16px; color: var(--dark); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; }
        
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
</style>
</head>
<body>
    <script src="../assets/responsive.js"></script>
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
            <a href="opening_balance.php" class="menu-item"><i class="fas fa-wallet"></i><span>Opening Balance</span></a>
            <a href="release_funds.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>Release Funds</span></a>
            <div class="menu-section">Reports</div>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="activity_logs.php" class="menu-item"><i class="fas fa-history"></i><span>Activity Logs</span></a>
            <div class="menu-section">Settings</div>
            <a href="users.php" class="menu-item"><i class="fas fa-users"></i><span>Manage Users</span></a>
            <a href="backup.php" class="menu-item active"><i class="fas fa-database"></i><span>Backup</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-database"></i> <?php echo $pageTitle; ?></h1>
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

        <div class="grid">
            <!-- Create Backup -->
            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Create New Backup</h3>
                <p style="color: #666; margin-bottom: 20px; font-size: 13px;">
                    Create a full backup of the expense database including all tables and data.
                </p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="backup">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-download"></i> Create Backup Now
                    </button>
                </form>
            </div>

            <!-- Backup List -->
            <div class="card">
                <h3><i class="fas fa-list"></i> Backup History</h3>
                <?php if (count($backups) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><i class="fas fa-file-sql"></i> <?php echo htmlspecialchars($backup['name']); ?></td>
                            <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                            <td><?php echo date('d M Y H:i', $backup['date']); ?></td>
                            <td>
                                <a href="../backups/<?php echo $backup['name']; ?>" download class="btn btn-success btn-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; padding: 30px; color: #999;">No backups found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

