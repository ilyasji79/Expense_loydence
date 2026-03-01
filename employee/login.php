<?php
/**
 * Employee Login Page
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/employee_auth.php';

// If already logged in as employee, redirect to dashboard
if (isEmployeeLoggedIn()) {
    redirect(BASE_URL . '/employee/dashboard.php');
}

$error = '';
$success = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $result = employeeLogin($db, $username, $password);
        if ($result['success']) {
            redirect(BASE_URL . '/employee/dashboard.php', 'Welcome to Employee Portal!', 'success');
        } else {
            $error = $result['message'];
        }
    }
}

$siteName = getSetting($db, 'school_name', SITE_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - <?php echo $siteName; ?></title>
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
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; width: 100%; max-width: 450px; animation: slideUp 0.5s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .login-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 40px 30px; text-align: center; }
        .login-header .logo { width: 80px; height: 80px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 35px; }
        .login-header h1 { font-size: 22px; font-weight: 600; margin-bottom: 5px; }
        .login-header p { font-size: 14px; opacity: 0.9; }
        .login-body { padding: 40px 30px; }
        .form-control { padding: 14px 14px 14px 45px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px; font-family: inherit; transition: all 0.3s ease; }
        .form-control:focus { border-color: #2a5298; box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.1); }
        .input-group-text { background: transparent; border: 2px solid #e0e0e0; border-radius: 10px 0 0 10px; border-right: none; color: #666; }
        .btn-login { width: 100%; padding: 15px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-family: inherit; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(42, 82, 152, 0.3); }
        .login-footer { text-align: center; padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; }
        .login-footer a { color: #1e3c72; text-decoration: none; font-weight: 500; }
        .login-footer a:hover { text-decoration: underline; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .input-wrapper { position: relative; margin-bottom: 20px; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
        @media (max-width: 480px) { .login-container { border-radius: 15px; } .login-header { padding: 30px 20px; } .login-header h1 { font-size: 18px; } .login-body { padding: 30px 20px; } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-user-tie"></i>
            </div>
            <h1>Employee Portal</h1>
            <p><?php echo $siteName; ?></p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autocomplete="username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <p class="mb-2">Need to access admin portal?</p>
            <a href="../login.php"><i class="fas fa-arrow-left"></i> Back to Main Login</a>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

