<?php
/**
 * Employee - Update Profile
 * Expense Management ERP - Loydence Academy
 * 
 * Allows employee to update: phone, address, emergency contact, profile photo
 * Does NOT allow: salary, role, employee code, department
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/employee_auth.php';
require_once __DIR__ . '/../includes/logger.php';

requireEmployeeLogin();

$employee = getCurrentEmployee($db);
$employeeId = $employee['id'];

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        // Sanitize inputs - employee can only update these fields
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $emergency_contact_name = sanitize($_POST['emergency_contact_name'] ?? '');
        $emergency_contact_phone = sanitize($_POST['emergency_contact_phone'] ?? '');
        
        // Handle photo upload
        $photoPath = $employee['photo']; // Keep existing photo by default
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $message = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
                $messageType = 'danger';
            } elseif ($_FILES['photo']['size'] > 2097152) { // 2MB max
                $message = 'File size too large. Maximum 2MB allowed.';
                $messageType = 'danger';
            } else {
                // Create uploads directory if not exists
                $uploadDir = __DIR__ . '/../uploads/employee_photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $newFilename = 'emp_' . $employeeId . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    // Delete old photo if exists and not default
                    if (!empty($employee['photo']) && file_exists(__DIR__ . '/../' . $employee['photo'])) {
                        unlink(__DIR__ . '/../' . $employee['photo']);
                    }
                    $photoPath = 'uploads/employee_photos/' . $newFilename;
                } else {
                    $message = 'Failed to upload photo. Please try again.';
                    $messageType = 'danger';
                }
            }
        }
        
        // Only update if no error occurred
        if (empty($message)) {
            // Update employee profile - only allowed fields
            $db->query("
                UPDATE employees SET
                    phone = ?,
                    address = ?,
                    emergency_contact_name = ?,
                    emergency_contact_phone = ?,
                    photo = ?
                WHERE id = ?
            ", [
                $phone,
                $address,
                $emergency_contact_name,
                $emergency_contact_phone,
                $photoPath,
                $employeeId
            ]);
            
            // Log activity
            logActivity($db, $_SESSION['user_id'], 'employee_profile_updated', "Employee profile updated: " . $employee['full_name']);
            
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            
            // Refresh employee data
            $employee = getCurrentEmployee($db);
        }
    }
}

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Update Profile';
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
        .form-control[readonly] { background: #f8f9fa; color: #6c757d; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #2a5298; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .profile-photo { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); margin-bottom: 15px; }
        .profile-avatar { width: 120px; height: 120px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; font-weight: 600; border: 4px solid var(--primary); margin-bottom: 15px; }
        .photo-upload { text-align: center; margin-bottom: 25px; }
        .photo-upload input[type="file"] { display: none; }
        .photo-upload label { cursor: pointer; display: inline-block; }
        
        .locked-field { opacity: 0.6; pointer-events: none; }
        .locked-badge { background: #6c757d; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px; }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar-small { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        
        .info-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .info-box h5 { color: #856404; margin-bottom: 10px; }
        .info-box ul { margin: 0; padding-left: 20px; color: #856404; }
        .info-box li { margin-bottom: 5px; }
        
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
            <a href="change-password.php" class="menu-item"><i class="fas fa-key"></i><span>Change Password</span></a>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-user-edit"></i> <?php echo $pageTitle; ?></h1>
            <div class="user-info">
                <div class="user-avatar-small"><?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($employee['full_name']); ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h5><i class="fas fa-info-circle"></i> What you can update:</h5>
            <ul>
                <li>Phone number</li>
                <li>Address</li>
                <li>Emergency contact information</li>
                <li>Profile photo</li>
            </ul>
        </div>

        <div class="card">
            <h3><i class="fas fa-camera"></i> Profile Photo</h3>
            <div class="photo-upload">
                <?php if (!empty($employee['photo']) && file_exists(__DIR__ . '/../' . $employee['photo'])): ?>
                    <img src="<?php echo BASE_URL . '/' . $employee['photo']; ?>" alt="Profile Photo" class="profile-photo">
                <?php else: ?>
                    <div class="profile-avatar"><?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?></div>
                <?php endif; ?>
                <label for="photo" class="btn btn-primary mt-2">
                    <i class="fas fa-upload"></i> Upload Photo
                </label>
                <input type="file" name="photo" id="photo" accept="image/*">
                <p class="text-muted mt-2">Max file size: 2MB. Allowed: JPG, PNG, GIF</p>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-edit"></i> Update Your Information</h3>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="employee_code">Employee Code <span class="locked-badge">Locked</span></label>
                            <input type="text" id="employee_code" class="form-control" value="<?php echo htmlspecialchars($employee['employee_code']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="locked-badge">Locked</span></label>
                            <input type="text" id="full_name" class="form-control" value="<?php echo htmlspecialchars($employee['full_name']); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email <span class="locked-badge">Locked</span></label>
                            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" placeholder="Enter phone number">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" class="form-control" rows="3" placeholder="Enter your address"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="department">Department <span class="locked-badge">Locked</span></label>
                            <input type="text" id="department" class="form-control" value="<?php echo htmlspecialchars($employee['department'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="designation">Designation <span class="locked-badge">Locked</span></label>
                            <input type="text" id="designation" class="form-control" value="<?php echo htmlspecialchars($employee['designation'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="base_salary">Base Salary <span class="locked-badge">Locked</span></label>
                            <input type="text" id="base_salary" class="form-control" value="<?php echo formatCurrency($employee['base_salary'] ?? 0); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <hr>
                <h4 class="mb-3"><i class="fas fa-phone-alt"></i> Emergency Contact</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" value="<?php echo htmlspecialchars($employee['emergency_contact_name'] ?? ''); ?>" placeholder="Contact person name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control" value="<?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? ''); ?>" placeholder="Contact phone number">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
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

