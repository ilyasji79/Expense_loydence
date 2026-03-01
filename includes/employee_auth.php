<?php
/**
 * Employee Authentication Functions
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/functions.php';

// Login employee
function employeeLogin($db, $username, $password) {
    // First check if user exists with employee role
    $user = $db->fetch("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.username = ? AND u.is_active = 1 AND r.role_name = 'employee'
    ", [$username]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid employee credentials'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid employee credentials'];
    }
    
    // Check user status (pending/approved/rejected)
    if (isset($user['status']) && $user['status'] === 'pending') {
        return ['success' => false, 'message' => 'Your account is pending approval. Please contact HR.'];
    }
    
    if (isset($user['status']) && $user['status'] === 'rejected') {
        return ['success' => false, 'message' => 'Your account has been rejected. Please contact HR.'];
    }
    
    // Check if employee record exists
    $employee = $db->fetch("SELECT * FROM employees WHERE user_id = ? AND is_active = 1", [$user['id']]);
    
    if (!$employee) {
        return ['success' => false, 'message' => 'Employee record not found. Please contact HR.'];
    }
    
    // Set session
    $_SESSION['employee_id'] = $employee['id'];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $employee['full_name'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = 'employee';
    $_SESSION['employee_login_time'] = time();
    $_SESSION['employee_code'] = $employee['employee_code'];
    
    // Log activity
    logActivity($db, $user['id'], 'employee_login', "Employee logged in: " . $employee['full_name'] . " (" . $employee['employee_code'] . ")");
    
    return ['success' => true, 'message' => 'Login successful'];
}

// Logout employee
function employeeLogout($db) {
    if (isset($_SESSION['employee_id'])) {
        $employee = $db->fetch("SELECT * FROM employees WHERE id = ?", [$_SESSION['employee_id']]);
        if ($employee) {
            logActivity($db, $_SESSION['user_id'], 'employee_logout', "Employee logged out: " . $employee['full_name']);
        }
    }
    
    // Clear employee-specific session
    unset($_SESSION['employee_id']);
    unset($_SESSION['employee_login_time']);
    unset($_SESSION['employee_code']);
    
    // If no other roles, destroy session
    if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] === 'employee') {
        session_destroy();
    }
    
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Check if employee is logged in
function isEmployeeLoggedIn() {
    return isset($_SESSION['employee_id']) && isset($_SESSION['employee_login_time']);
}

// Check employee session validity
function checkEmployeeSession() {
    if (!isEmployeeLoggedIn()) {
        return false;
    }
    
    $timeout = 7200; // 2 hours
    if (time() - $_SESSION['employee_login_time'] > $timeout) {
        employeeLogout($GLOBALS['db']);
        return false;
    }
    
    return true;
}

// Require employee login
function requireEmployeeLogin() {
    if (!checkEmployeeLogin()) {
        redirect(BASE_URL . '/employee/login.php', 'Please login to continue', 'error');
    }
}

// Alias for compatibility
function checkEmployeeLogin() {
    return checkEmployeeSession();
}

// Get current employee
function getCurrentEmployee($db) {
    if (!isset($_SESSION['employee_id'])) {
        return null;
    }
    return $db->fetch("SELECT * FROM employees WHERE id = ?", [$_SESSION['employee_id']]);
}

// Get employee by user ID
function getEmployeeByUserId($db, $userId) {
    return $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$userId]);
}

// Get employee by ID
function getEmployeeById($db, $id) {
    return $db->fetch("SELECT * FROM employees WHERE id = ?", [$id]);
}

// Get all employees
function getAllEmployees($db, $activeOnly = true) {
    $sql = "SELECT * FROM employees";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY full_name ASC";
    return $db->fetchAll($sql);
}

// Create employee
function createEmployee($db, $data) {
    // Generate employee code
    $year = date('Y');
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE employee_code LIKE ?", ["EMP-$year%"]);
    $result = $stmt->fetch();
    $count = $result['count'] + 1;
    $employeeCode = 'EMP-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    // Check if email exists
    $existing = $db->fetch("SELECT id FROM employees WHERE email = ?", [$data['email']]);
    if ($existing) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Check if user_id exists (if linking to user account)
    if (!empty($data['user_id'])) {
        $existingUser = $db->fetch("SELECT id FROM employees WHERE user_id = ?", [$data['user_id']]);
        if ($existingUser) {
            return ['success' => false, 'message' => 'User is already linked to another employee'];
        }
    }
    
    $fullName = $data['first_name'] . ' ' . $data['last_name'];
    
    $db->query("
        INSERT INTO employees (
            user_id, employee_code, first_name, last_name, full_name, email, phone,
            date_of_birth, gender, nationality, marital_status, address, department,
            designation, join_date, employment_type, base_salary, hourly_rate,
            bank_name, bank_account_number, emirates_id, passport_number, passport_expiry,
            visa_status, emergency_contact_name, emergency_contact_phone, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [
        $data['user_id'] ?? null,
        $employeeCode,
        $data['first_name'],
        $data['last_name'],
        $fullName,
        $data['email'],
        $data['phone'] ?? '',
        $data['date_of_birth'] ?? null,
        $data['gender'] ?? 'male',
        $data['nationality'] ?? '',
        $data['marital_status'] ?? 'single',
        $data['address'] ?? '',
        $data['department'] ?? '',
        $data['designation'] ?? '',
        $data['join_date'],
        $data['employment_type'] ?? 'full_time',
        $data['base_salary'] ?? 0,
        $data['hourly_rate'] ?? 0,
        $data['bank_name'] ?? '',
        $data['bank_account_number'] ?? '',
        $data['emirates_id'] ?? '',
        $data['passport_number'] ?? '',
        $data['passport_expiry'] ?? null,
        $data['visa_status'] ?? '',
        $data['emergency_contact_name'] ?? '',
        $data['emergency_contact_phone'] ?? '',
        $_SESSION['user_id'] ?? null
    ]);
    
    $employeeId = $db->lastInsertId();
    
    // Log activity
    logActivity($db, $_SESSION['user_id'] ?? null, 'employee_created', "New employee created: $fullName ($employeeCode)");
    
    return ['success' => true, 'message' => 'Employee created successfully', 'employee_id' => $employeeId];
}

// Update employee
function updateEmployee($db, $employeeId, $data) {
    $fullName = $data['first_name'] . ' ' . $data['last_name'];
    
    $db->query("
        UPDATE employees SET
            first_name = ?,
            last_name = ?,
            full_name = ?,
            email = ?,
            phone = ?,
            date_of_birth = ?,
            gender = ?,
            nationality = ?,
            marital_status = ?,
            address = ?,
            department = ?,
            designation = ?,
            employment_type = ?,
            base_salary = ?,
            hourly_rate = ?,
            bank_name = ?,
            bank_account_number = ?,
            emirates_id = ?,
            passport_number = ?,
            passport_expiry = ?,
            visa_status = ?,
            emergency_contact_name = ?,
            emergency_contact_phone = ?
        WHERE id = ?
    ", [
        $data['first_name'],
        $data['last_name'],
        $fullName,
        $data['email'],
        $data['phone'] ?? '',
        $data['date_of_birth'] ?? null,
        $data['gender'] ?? 'male',
        $data['nationality'] ?? '',
        $data['marital_status'] ?? 'single',
        $data['address'] ?? '',
        $data['department'] ?? '',
        $data['designation'] ?? '',
        $data['employment_type'] ?? 'full_time',
        $data['base_salary'] ?? 0,
        $data['hourly_rate'] ?? 0,
        $data['bank_name'] ?? '',
        $data['bank_account_number'] ?? '',
        $data['emirates_id'] ?? '',
        $data['passport_number'] ?? '',
        $data['passport_expiry'] ?? null,
        $data['visa_status'] ?? '',
        $data['emergency_contact_name'] ?? '',
        $data['emergency_contact_phone'] ?? '',
        $employeeId
    ]);
    
    logActivity($db, $_SESSION['user_id'] ?? null, 'employee_updated', "Employee updated: $fullName (ID: $employeeId)");
    
    return ['success' => true, 'message' => 'Employee updated successfully'];
}

// Delete (deactivate) employee
function deleteEmployee($db, $employeeId) {
    $employee = getEmployeeById($db, $employeeId);
    if (!$employee) {
        return ['success' => false, 'message' => 'Employee not found'];
    }
    
    $db->query("UPDATE employees SET is_active = 0 WHERE id = ?", [$employeeId]);
    
    logActivity($db, $_SESSION['user_id'] ?? null, 'employee_deleted', "Employee deactivated: " . $employee['full_name'] . " (ID: $employeeId)");
    
    return ['success' => true, 'message' => 'Employee deactivated successfully'];
}

// Create employee user account
function createEmployeeUserAccount($db, $employeeId, $username, $password) {
    $employee = getEmployeeById($db, $employeeId);
    if (!$employee) {
        return ['success' => false, 'message' => 'Employee not found'];
    }
    
    // Check if username exists
    $existing = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email exists
    $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$employee['email']]);
    if ($existing) {
        return ['success' => false, 'message' => 'Email already exists in user accounts'];
    }
    
    // Get employee role ID
    $role = $db->fetch("SELECT id FROM roles WHERE role_name = 'employee'");
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user with 'pending' status - requires HR approval
    $db->query("
        INSERT INTO users (username, password, email, full_name, role_id, phone, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ", [
        $username,
        $hashedPassword,
        $employee['email'],
        $employee['full_name'],
        $role['id'],
        $employee['phone']
    ]);
    
    $userId = $db->lastInsertId();
    
    // Link employee to user
    $db->query("UPDATE employees SET user_id = ? WHERE id = ?", [$userId, $employeeId]);
    
    logActivity($db, $_SESSION['user_id'] ?? null, 'employee_user_created', "Employee user account created: $username for " . $employee['full_name'] . " (Status: Pending - requires HR approval)");
    
    return ['success' => true, 'message' => 'Employee user account created successfully. Account is pending approval.', 'user_id' => $userId];
}

