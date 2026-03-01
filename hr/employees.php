<?php
/**
 * HR - Manage Employees
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/employee_auth.php';
require_once __DIR__ . '/../includes/employee_functions.php';
require_once __DIR__ . '/../includes/logger.php';

// Require admin or HR login
requireLogin();
if (!hasRole($db, 'admin') && !hasRole($db, 'hr_manager')) {
    redirect(BASE_URL . '/dashboard.php', 'Access denied', 'error');
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // CSRF check
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $message = 'Invalid request';
            $messageType = 'danger';
        } else {
            switch ($_POST['action']) {
                case 'create':
                    $result = createEmployee($db, [
                        'first_name' => sanitize($_POST['first_name']),
                        'last_name' => sanitize($_POST['last_name']),
                        'email' => sanitize($_POST['email']),
                        'phone' => sanitize($_POST['phone'] ?? ''),
                        'date_of_birth' => $_POST['date_of_birth'] ?? null,
                        'gender' => $_POST['gender'] ?? 'male',
                        'nationality' => sanitize($_POST['nationality'] ?? ''),
                        'marital_status' => $_POST['marital_status'] ?? 'single',
                        'address' => sanitize($_POST['address'] ?? ''),
                        'department' => sanitize($_POST['department'] ?? ''),
                        'designation' => sanitize($_POST['designation'] ?? ''),
                        'join_date' => $_POST['join_date'],
                        'employment_type' => $_POST['employment_type'] ?? 'full_time',
                        'base_salary' => floatval($_POST['base_salary'] ?? 0),
                        'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
                        'bank_name' => sanitize($_POST['bank_name'] ?? ''),
                        'bank_account_number' => sanitize($_POST['bank_account_number'] ?? ''),
                        'emirates_id' => sanitize($_POST['emirates_id'] ?? ''),
                        'passport_number' => sanitize($_POST['passport_number'] ?? ''),
                        'passport_expiry' => $_POST['passport_expiry'] ?? null,
                        'visa_status' => sanitize($_POST['visa_status'] ?? ''),
                        'emergency_contact_name' => sanitize($_POST['emergency_contact_name'] ?? ''),
                        'emergency_contact_phone' => sanitize($_POST['emergency_contact_phone'] ?? '')
                    ]);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                    break;
                    
                case 'update':
                    $result = updateEmployee($db, intval($_POST['employee_id']), [
                        'first_name' => sanitize($_POST['first_name']),
                        'last_name' => sanitize($_POST['last_name']),
                        'email' => sanitize($_POST['email']),
                        'phone' => sanitize($_POST['phone'] ?? ''),
                        'date_of_birth' => $_POST['date_of_birth'] ?? null,
                        'gender' => $_POST['gender'] ?? 'male',
                        'nationality' => sanitize($_POST['nationality'] ?? ''),
                        'marital_status' => $_POST['marital_status'] ?? 'single',
                        'address' => sanitize($_POST['address'] ?? ''),
                        'department' => sanitize($_POST['department'] ?? ''),
                        'designation' => sanitize($_POST['designation'] ?? ''),
                        'employment_type' => $_POST['employment_type'] ?? 'full_time',
                        'base_salary' => floatval($_POST['base_salary'] ?? 0),
                        'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
                        'bank_name' => sanitize($_POST['bank_name'] ?? ''),
                        'bank_account_number' => sanitize($_POST['bank_account_number'] ?? ''),
                        'emirates_id' => sanitize($_POST['emirates_id'] ?? ''),
                        'passport_number' => sanitize($_POST['passport_number'] ?? ''),
                        'passport_expiry' => $_POST['passport_expiry'] ?? null,
                        'visa_status' => sanitize($_POST['visa_status'] ?? ''),
                        'emergency_contact_name' => sanitize($_POST['emergency_contact_name'] ?? ''),
                        'emergency_contact_phone' => sanitize($_POST['emergency_contact_phone'] ?? '')
                    ]);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                    break;
                    
                case 'delete':
                    $result = deleteEmployee($db, intval($_POST['employee_id']));
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                    break;
                    
                case 'create_user':
                    $result = createEmployeeUserAccount($db, intval($_POST['employee_id']), sanitize($_POST['username']), $_POST['password']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                    break;
            }
        }
    }
}

// Get all employees
$employees = getAllEmployees($db);

// Get departments for filter
$departments = getDepartments($db);

$siteName = getSetting($db, 'school_name', SITE_NAME);
$pageTitle = 'Manage Employees';
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
        :root { --primary: #1e3c72; --secondary: #2a5298; --success: #28a745; --danger: #dc3545; --warning: #ffc107; --info: #17a2b8; --dark: #343a40; --light: #f8f9fa; --sidebar-width: 260px; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; min-height: 100vh; overflow-x: hidden; }
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
        .menu-item span { font-size: 14px; margin-left: 10px; }
        .menu-item .badge { margin-left: auto; background: var(--danger); padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        
        .main-content { margin-left: var(--sidebar-width); padding: 25px; min-height: 100vh; }
        .top-header { background: white; padding: 15px 25px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-header h1 { font-size: 22px; color: var(--dark); }
        .fullscreen-btn { background: none; border: none; font-size: 20px; color: var(--dark); cursor: pointer; padding: 5px 10px; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .user-details { text-align: right; }
        .user-details .name { font-size: 14px; font-weight: 600; color: var(--dark); }
        .user-details .role { font-size: 12px; color: #666; }
        
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h3 { font-size: 16px; color: var(--dark); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--secondary); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: var(--dark); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; background: #f8f9fa; }
        table td { font-size: 13px; color: var(--dark); }
        table tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--dark); }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: inherit; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .modal-header h3 { margin: 0; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }
        
        .action-btns { display: flex; gap: 5px; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .top-header { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
            <a href="employees.php" class="menu-item active"><i class="fas fa-users"></i><span>Employees</span></a>
            <a href="attendance.php" class="menu-item"><i class="fas fa-clock"></i><span>Attendance</span></a>
            <a href="payroll.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span>Payroll</span></a>
            <a href="leave_requests.php" class="menu-item"><i class="fas fa-calendar-minus"></i><span>Leave Requests</span></a>
            <a href="employee_reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div class="menu-section">Account</div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-users"></i> <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <button class="fullscreen-btn" onclick="toggleFullScreen()" title="Toggle Fullscreen"><i class="fas fa-expand"></i></button>
                <div class="user-info">
                    <div class="user-details">
                        <div class="name"><?php echo $_SESSION['full_name']; ?></div>
                        <div class="role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role_name'])); ?></div>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Employee List -->
        <div class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3><i class="fas fa-list"></i> All Employees (<?php echo count($employees); ?>)</h3>
                <button class="btn btn-primary" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Add Employee
                </button>
            </div>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name, code, department..." onkeyup="searchTable()">
                <select class="form-control" style="width: 200px;" onchange="filterDepartment(this.value)">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['department']); ?>"><?php echo htmlspecialchars($dept['department']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="table-responsive">
                <table id="employeeTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Join Date</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($employees) > 0): ?>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($emp['employee_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['designation'] ?? '-'); ?></td>
                                <td><?php echo formatDate($emp['join_date']); ?></td>
                                <td><?php echo formatCurrency($emp['base_salary']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $emp['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $emp['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn btn-primary btn-sm" onclick="editEmployee(<?php echo $emp['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (!$emp['user_id']): ?>
                                            <button class="btn btn-success btn-sm" onclick="createUser(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['full_name']); ?>')" title="Create Login">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-danger btn-sm" onclick="deleteEmployee(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['full_name']); ?>')" title="Deactivate">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4">No employees found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Employee</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g., Teaching, Admin">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g., Teacher, Accountant">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Join Date *</label>
                            <input type="date" name="join_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Employment Type</label>
                            <select name="employment_type" class="form-control">
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Base Salary (QAR)</label>
                            <input type="number" name="base_salary" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Hourly Rate (QAR)</label>
                            <input type="number" name="hourly_rate" class="form-control" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nationality</label>
                            <input type="text" name="nationality" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Employee</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Employee</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="employee_id" id="edit_employee_id">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" id="edit_department" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="designation" id="edit_designation" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Employment Type</label>
                            <select name="employment_type" id="edit_employment_type" class="form-control">
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Base Salary (QAR)</label>
                            <input type="number" name="base_salary" id="edit_base_salary" class="form-control" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Employee</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Employee Login</h3>
                <button class="modal-close" onclick="closeModal('userModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="employee_id" id="user_employee_id">
                
                <div class="form-group">
                    <label>Employee</label>
                    <input type="text" id="user_employee_name" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Create Login</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Confirm Deactivation</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="employee_id" id="delete_employee_id">
                
                <p>Are you sure you want to deactivate <strong id="delete_employee_name"></strong>?</p>
                <p class="text-muted">This action can be reversed later.</p>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Deactivate</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Employee data for JavaScript
        const employees = <?php echo json_encode($employees); ?>;
        
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        function editEmployee(id) {
            const emp = employees.find(e => e.id === id);
            if (emp) {
                document.getElementById('edit_employee_id').value = emp.id;
                document.getElementById('edit_first_name').value = emp.first_name;
                document.getElementById('edit_last_name').value = emp.last_name;
                document.getElementById('edit_email').value = emp.email;
                document.getElementById('edit_phone').value = emp.phone || '';
                document.getElementById('edit_department').value = emp.department || '';
                document.getElementById('edit_designation').value = emp.designation || '';
                document.getElementById('edit_employment_type').value = emp.employment_type || 'full_time';
                document.getElementById('edit_base_salary').value = emp.base_salary || 0;
                openModal('editModal');
            }
        }
        
        function createUser(id, name) {
            document.getElementById('user_employee_id').value = id;
            document.getElementById('user_employee_name').value = name;
            openModal('userModal');
        }
        
        function deleteEmployee(id, name) {
            document.getElementById('delete_employee_id').value = id;
            document.getElementById('delete_employee_name').textContent = name;
            openModal('deleteModal');
        }
        
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('employeeTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
            }
        }
        
        function filterDepartment(dept) {
            const table = document.getElementById('employeeTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                if (!dept) {
                    row.style.display = '';
                } else {
                    row.style.display = row.textContent.indexOf(dept) > -1 ? '' : 'none';
                }
            }
        }
        
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
    </script>
</body>
</html>

