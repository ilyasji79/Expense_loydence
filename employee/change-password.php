<?php
/**
 * Employee - Change Password
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/employee_auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireEmployeeLogin();

$employee = getCurrentEmployee($db);
$userId = $_SESSION['user_id'];

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = 'All fields are required.';
            $messageType = 'danger';
        } elseif (strlen($newPassword) < 6) {
            $message = 'New password must be at least 6 characters.';
            $messageType = 'danger';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match.';
            $messageType = 'danger';
        } else {
            // Verify current password
            $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
            
            if (!$user) {
                $message = 'User not found.';
                $messageType = 'danger';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'danger';
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
                
                // Log activity
                logActivity($db, $userId, 'employee_password_changed', "Password changed for employee: " . $employee['full_name']);
                
                $message = 'Password changed successfully!';
                $messageType = 'success';
                
                // Clear POST data to prevent re-submission
                $_POST = [];
            }
        }
    }
}

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Change Password';
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
    <style>
        :root { --primary: #1e3c72; --sidebar-width: 260px; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; margin: 0; }
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 0%, #2a5298 100%); color: white; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { width: 70px; height: 70px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 30px; }
        .sidebar-header h2 { font-size: 16px; font-weight: 600; margin: 0; }
        .sidebar-header p { font-size: 11px; opacity: 0.8; margin: 5px 0 0; }
        .sidebar-menu { padding: 15px 0; }
        .menu-section { padding: 8px 20px; font-size: 11px; text-transform: uppercase; opacity: 0.6; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; text-decoration: none; color: white; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: #ffc107; }
        .menu-item i { width: 25px; }
        .menu-item span { margin-left: 10px; }
        .main-content { margin-left: var(--sidebar-width); padding: 25px; min-height: 100vh; }
        .top-header { background: white; padding: 15px 25px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-header h1 { font-size: 22px; color: #343a40; margin: 0; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h3 { font-size: 16px; color: #343a40; margin: 0 0 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #343a40; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: inherit; transition: border-color 0.3s; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #2a5298; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .password-requirements { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .password-requirements h5 { font-size: 14px; margin-bottom: 10px; color: #343a40; }
        .password-requirements ul { margin: 0; padding-left: 20px; }
        .password-requirements li { font-size: 13px; color: #666; margin-bottom: 5px; }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        
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
            <a href="leave.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>My Leave</span></a>
            <div class="menu-section">Account</div>
            <a href="change-password.php" class="menu-item active"><i class="fas fa-key"></i><span>Change Password</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-key"></i> <?php echo $pageTitle; ?></h1>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($employee['full_name']); ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-lock"></i> Change Your Password</h3>
            
            <div class="password-requirements">
                <h5><i class="fas fa-info-circle"></i> Password Requirements</h5>
                <ul>
                    <li>At least 6 characters long</li>
                    <li>Use a mix of letters and numbers for better security</li>
                    <li>Avoid using easily guessable passwords</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label for="current_password"><i class="fas fa-lock"></i> Current Password *</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" 
                           required autocomplete="current-password" placeholder="Enter your current password">
                </div>
                
                <div class="form-group">
                    <label for="new_password"><i class="fas fa-key"></i> New Password *</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" 
                           required minlength="6" autocomplete="new-password" placeholder="Enter new password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-key"></i> Confirm New Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                           required minlength="6" autocomplete="new-password" placeholder="Re-enter new password">
                </div>
                
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

