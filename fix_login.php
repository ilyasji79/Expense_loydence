<?php
/**
 * Auto-Fix Script - Creates employees and fixes passwords
 * Run this to fix login issues
 */

echo "============================================================\n";
echo "  AUTO-FIX LOGIN ISSUES\n";
echo "============================================================\n\n";

// Prevent session issues
ini_set('session.output_buffering', 0);
ob_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = new Database();

// ============================================================
// FIX 1: CREATE EMPLOYEE RECORD FOR TEACHER1
// ============================================================
echo "[1] Checking for employee 'teacher1'...\n";

$teacher1User = $db->fetch("SELECT * FROM users WHERE username = 'teacher1'");

if ($teacher1User) {
    echo "  - Found user account: teacher1 (User ID: {$teacher1User['id']})\n";
    
    // Check if employee exists
    $employee = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$teacher1User['id']]);
    
    if (!$employee) {
        // Create employee record
        echo "  - Creating employee record...\n";
        
        $db->query("
            INSERT INTO employees (
                user_id, employee_code, first_name, last_name, full_name, 
                email, phone, department, designation, join_date, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $teacher1User['id'],
            'EMP-2024-0001',
            'Teacher', 
            'One',
            'Teacher One',
            $teacher1User['email'] ?? 'teacher1@loydence.edu.qa',
            '',
            'Teaching',
            'Teacher',
            date('Y-m-d'),
            1
        ]);
        
        $empId = $db->lastInsertId();
        echo "  ✓ Created employee record (ID: $empId)\n";
    } else {
        echo "  ✓ Employee record exists (ID: {$employee['id']})\n";
    }
} else {
    echo "  - User account 'teacher1' not found\n";
}

// ============================================================
// FIX 2: RESET TEACHER1 PASSWORD
// ============================================================
echo "\n[2] Resetting teacher1 password to 'teacher123'...\n";

$newPassword = 'teacher123';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$db->query("UPDATE users SET password = ? WHERE username = 'teacher1'", [$newHash]);
echo "  ✓ Password updated!\n";

// ============================================================
// FIX 3: CREATE ZARA123 EMPLOYEE (if username is zara)
// ============================================================
echo "\n[3] Checking for employee 'zara' or 'zara123'...\n";

// Check if zara exists as user or needs to be created
$zaraUser = $db->fetch("SELECT * FROM users WHERE username = 'zara'");

if (!$zaraUser) {
    // Check if there's any user with zara in the name
    $zaraUser = $db->fetch("SELECT * FROM users WHERE username = 'zara123'");
}

if (!$zaraUser) {
    echo "  - Creating user account for Zara...\n";
    
    // Get employee role
    $role = $db->fetch("SELECT id FROM roles WHERE role_name = 'employee'");
    
    $zaraPassword = 'zara123';
    $zaraHash = password_hash($zaraPassword, PASSWORD_DEFAULT);
    
    $db->query("
        INSERT INTO users (username, password, email, full_name, role_id, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ", [
        'zara',
        $zaraHash,
        'zara@loydence.edu.qa',
        'Zara Teacher',
        $role['id'],
        1
    ]);
    
    $zaraUserId = $db->lastInsertId();
    echo "  ✓ Created user account: zara / $zaraPassword\n";
    
    // Create employee record for Zara
    $db->query("
        INSERT INTO employees (
            user_id, employee_code, first_name, last_name, full_name, 
            email, phone, department, designation, join_date, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [
        $zaraUserId,
        'EMP-2024-0002',
        'Zara', 
        'Teacher',
        'Zara Teacher',
        'zara@loydence.edu.qa',
        '',
        'Teaching',
        'Teacher',
        date('Y-m-d'),
        1
    ]);
    
    echo "  ✓ Created employee record for Zara\n";
} else {
    echo "  - User account exists: {$zaraUser['username']}\n";
    
    // Check if employee record exists
    $zaraEmployee = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$zaraUser['id']]);
    
    if (!$zaraEmployee) {
        $db->query("
            INSERT INTO employees (
                user_id, employee_code, first_name, last_name, full_name, 
                email, phone, department, designation, join_date, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $zaraUser['id'],
            'EMP-2024-0002',
            'Zara', 
            'Teacher',
            'Zara Teacher',
            $zaraUser['email'],
            '',
            'Teaching',
            'Teacher',
            date('Y-m-d'),
            1
        ]);
        echo "  ✓ Created employee record for Zara\n";
    }
}

// ============================================================
// VERIFY FIXES
// ============================================================
echo "\n[4] Verifying fixes...\n";

$users = $db->fetchAll("SELECT * FROM users WHERE username IN ('admin', 'hr', 'teacher1', 'zara')");

foreach ($users as $u) {
    $role = $db->fetch("SELECT role_name FROM roles WHERE id = ?", [$u['role_id']]);
    $employee = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$u['id']]);
    
    echo "  - {$u['username']} ({$role['role_name']}):\n";
    echo "    Password OK: " . (password_verify($u['username'] === 'teacher1' ? 'teacher123' : ($u['username'] === 'zara' ? 'zara123' : 'admin123'), $u['password']) ? '✓' : '✗') . "\n";
    echo "    Employee Linked: " . ($employee ? '✓' : '✗') . "\n";
}

// ============================================================
// SUMMARY
// ============================================================
echo "\n============================================================\n";
echo "  FIX COMPLETE!\n";
echo "============================================================\n\n";

echo "Login Credentials:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  ADMIN PANEL (login.php):\n";
echo "    Username: admin\n";
echo "    Password: admin123\n\n";
echo "  HR PANEL (login.php):\n";
echo "    Username: hr\n";
echo "    Password: admin123\n\n";
echo "  EMPLOYEE PORTAL (employee/login.php):\n";
echo "    Username: teacher1\n";
echo "    Password: teacher123\n";
echo "    OR\n";
echo "    Username: zara\n";
echo "    Password: zara123\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Try logging in now!\n";

