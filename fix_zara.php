<?php
/**
 * Simple Fix Script - Handles duplicate email issue
 */

echo "============================================================\n";
echo "  SIMPLE FIX FOR ZARA LOGIN\n";
echo "============================================================\n\n";

// Prevent session issues
ini_set('session.output_buffering', 0);
ob_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = new Database();

echo "[1] Checking for zara user...\n";

// Find user with zara in username or email
$zaraUser = $db->fetch("SELECT * FROM users WHERE username LIKE '%zara%' OR email LIKE '%zara%'");

if (!$zaraUser) {
    echo "  - No zara user found. Creating one...\n";
    
    // Get employee role
    $role = $db->fetch("SELECT id FROM roles WHERE role_name = 'employee'");
    
    // Use a unique email
    $email = 'zara.teacher@loydence.edu.qa';
    
    // Check if this email exists too
    $exists = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($exists) {
        $email = 'zara.staff@loydence.edu.qa';
    }
    
    $zaraPassword = 'zara123';
    $zaraHash = password_hash($zaraPassword, PASSWORD_DEFAULT);
    
    $db->query("
        INSERT INTO users (username, password, email, full_name, role_id, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ", [
        'zara',
        $zaraHash,
        $email,
        'Zara Teacher',
        $role['id'],
        1
    ]);
    
    $zaraUserId = $db->lastInsertId();
    echo "  ✓ Created user: zara / $zaraPassword (email: $email)\n";
    
    // Create employee record
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
        $email,
        '',
        'Teaching',
        'Teacher',
        date('Y-m-d'),
        1
    ]);
    
    echo "  ✓ Created employee record for Zara\n";
} else {
    echo "  - Found user: {$zaraUser['username']} (ID: {$zaraUser['id']})\n";
    
    // Check if employee exists
    $emp = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$zaraUser['id']]);
    
    if (!$emp) {
        // Create employee record
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
        echo "  ✓ Created employee record\n";
    } else {
        echo "  ✓ Employee record exists\n";
    }
    
    // Reset password to zara123
    $zaraHash = password_hash('zara123', PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password = ? WHERE id = ?", [$zaraHash, $zaraUser['id']]);
    echo "  ✓ Password reset to: zara123\n";
}

// ============================================================
// FINAL VERIFICATION
// ============================================================
echo "\n[2] Final Verification...\n";

$allUsers = $db->fetchAll("SELECT * FROM users WHERE username IN ('admin', 'hr', 'teacher1', 'zara')");

foreach ($allUsers as $u) {
    $role = $db->fetch("SELECT role_name FROM roles WHERE id = ?", [$u['role_id']]);
    $emp = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$u['id']]);
    
    $testPass = $u['username'] === 'teacher1' ? 'teacher123' : ($u['username'] === 'zara' ? 'zara123' : 'admin123');
    $passOk = password_verify($testPass, $u['password']);
    
    echo "  {$u['username']} ({$role['role_name']}): Password=" . ($passOk ? 'OK' : 'FAIL') . ", Employee=" . ($emp ? 'Yes' : 'No') . "\n";
}

echo "\n============================================================\n";
echo "  DONE! Try these logins:\n";
echo "============================================================\n";
echo "  Admin: admin / admin123\n";
echo "  HR: hr / admin123\n";
echo "  Employee: teacher1 / teacher123\n";
echo "  Employee: zara / zara123\n";
echo "============================================================\n";

