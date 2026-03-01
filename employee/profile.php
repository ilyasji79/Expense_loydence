<?php
/**
 * Employee - My Profile
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/employee_auth.php';

requireEmployeeLogin();

$employee = getCurrentEmployee($db);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'My Profile';
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
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .profile-avatar { width: 100px; height: 100px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 40px; font-weight: 600; }
        .profile-info h2 { font-size: 24px; color: #343a40; margin: 0 0 5px; }
        .profile-info p { color: #666; margin: 0; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .info-item { margin-bottom: 15px; }
        .info-item label { display: block; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .info-item p { font-size: 14px; color: #343a40; margin: 0; font-weight: 500; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .profile-header { flex-direction: column; text-align: center; }
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
            <a href="profile.php" class="menu-item active"><i class="fas fa-user"></i><span>My Profile</span></a>
            <a href="attendance.php" class="menu-item"><i class="fas fa-clock"></i><span>My Attendance</span></a>
            <a href="salary.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>My Salary</span></a>
            <a href="leave.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>My Leave</span></a>
            <div class="menu-section">Account</div>
<a href="change-password.php" class="menu-item"><i class="fas fa-key"></i><span>Change Password</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-user"></i> <?php echo $pageTitle; ?></h1>
            <a href="update-profile.php" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
        </div>

        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($employee['full_name']); ?></h2>
                    <p><?php echo htmlspecialchars($employee['designation'] ?? 'Employee'); ?></p>
                    <p><strong>Code:</strong> <?php echo htmlspecialchars($employee['employee_code']); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-info-circle"></i> Personal Information</h3>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($employee['email']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <p><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Date of Birth</label>
                        <p><?php echo $employee['date_of_birth'] ? formatDate($employee['date_of_birth']) : 'N/A'; ?></p>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <label>Gender</label>
                        <p><?php echo ucfirst($employee['gender'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Nationality</label>
                        <p><?php echo htmlspecialchars($employee['nationality'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Marital Status</label>
                        <p><?php echo ucfirst($employee['marital_status'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-briefcase"></i> Employment Information</h3>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <label>Department</label>
                        <p><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Designation</label>
                        <p><?php echo htmlspecialchars($employee['designation'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Employment Type</label>
                        <p><?php echo ucfirst(str_replace('_', ' ', $employee['employment_type'] ?? 'N/A')); ?></p>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <label>Join Date</label>
                        <p><?php echo $employee['join_date'] ? formatDate($employee['join_date']) : 'N/A'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Base Salary</label>
                        <p><?php echo formatCurrency($employee['base_salary'] ?? 0); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-id-card"></i> Document Information</h3>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <label>Emirates ID</label>
                        <p><?php echo htmlspecialchars($employee['emirates_id'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Passport Number</label>
                        <p><?php echo htmlspecialchars($employee['passport_number'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <label>Visa Status</label>
                        <p><?php echo htmlspecialchars($employee['visa_status'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Bank Account</label>
                        <p><?php echo htmlspecialchars($employee['bank_account_number'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

