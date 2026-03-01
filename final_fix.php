<?php
/**
 * Complete Fix Script - Properly sets up all logins
 */

echo "============================================================\n";
echo "  COMPLETE LOGIN FIX\n";
echo "============================================================\n\n";

ini_set('session.output_buffering', 0);
ob_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = new Database();

// ============================================================
// FIX 1: Teacher1
// ============================================================
echo "[1] Setting up teacher1...\n";

$teacher1 = $db->fetch("SELECT * FROM users WHERE username = 'teacher1'");
if ($teacher1) {
    // Reset password to teacher123
    $hash = password_hash('teacher123', PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password = ? WHERE username = 'teacher1'", [$hash]);
    echo "  ✓ Password set to: teacher123\n";
    
    // Ensure employee record exists
    $emp = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$teacher1['id']]);
    if (!$emp) {
        $db->query("
            INSERT INTO employees (user_id, employee_code, first_name, last_name, full_name, email, department, designation, join_date, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $teacher1['id'], 'EMP-2024-0001', 'Teacher', 'One', 'Teacher One', 
            $teacher1['email'] ?? 'teacher1@loydence.edu.qa', 'Teaching', 'Teacher', date('Y-m-d'), 1
        ]);
        echo "  ✓ Created employee record\n";
    } else {
        echo "  ✓ Employee record exists\n";
    }
} else {
    echo "  ✗ User teacher1 not found\n";
}

// ============================================================
// FIX 2: Zara
// ============================================================
echo "\n[2] Setting up zara...\n";

// Check if zara user exists
$zara = $db->fetch("SELECT * FROM users WHERE username = 'zara'");

if (!$zara) {
    // Create zara user
    $role = $db->fetch("SELECT id FROM roles WHERE role_name = 'employee'");
    
    $email = 'zara@loydence.edu.qa';
    $exists = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($exists) {
        $email = 'zara1@loydence.edu.qa';
    }
    
    $hash = password_hash('zara123', PASSWORD_DEFAULT);
    $db->query("INSERT INTO users (username, password, email, full_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)",
        ['zara', $hash, $email, 'Zara Teacher', $role['id'], 1]);
    
    $zaraId = $db->lastInsertId();
    echo "  ✓ Created user: zara / zara123\n";
    
    // Create employee record
    $db->query("
        INSERT INTO employees (user_id, employee_code, first_name, last_name, full_name, email, department, designation, join_date, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [$zaraId, 'EMP-2024-0002', 'Zara', 'Teacher', 'Zara Teacher', $email, 'Teaching', 'Teacher', date('Y-m-d'), 1]);
    echo "  ✓ Created employee record\n";
} else {
    // Reset password
    $hash = password_hash('zara123', PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password = ? WHERE username = 'zara'", [$hash]);
    echo "  ✓ Password reset to: zara123\n";
    
    // Ensure employee exists
    $emp = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$zara['id']]);
    if (!$emp) {
        $db->query("
            INSERT INTO employees (user_id, employee_code, first_name, last_name, full_name, email, department, designation, join_date, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [$zara['id'], 'EMP-2024-0002', 'Zara', 'Teacher', 'Zara Teacher', $zara['email'], 'Teaching', 'Teacher', date('Y-m-d'), 1]);
        echo "  ✓ Created employee record\n";
    }
}

// ============================================================
// VERIFY
// ============================================================
echo "\n[3] Verification...\n";

$users = $db->fetchAll("SELECT * FROM users WHERE username IN ('admin', 'hr', 'teacher1', 'zara')");

foreach ($users as $u) {
    $role = $db->fetch("SELECT role_name FROM roles WHERE id = ?", [$u['role_id']]);
    $emp = $db->fetch("SELECT * FROM employees WHERE user_id = ?", [$u['id']]);
    
    $testPass = $u['username'] === 'teacher1' ? 'teacher123' : ($u['username'] === 'zara' ? 'zara123' : 'admin123');
    $ok = password_verify($testPass, $u['password']);
    
    echo "  {$u['username']}: PASSWORD=" . ($ok ? '✓' : '✗') . ", EMPLOYEE=" . ($emp ? '✓' : '✗') . "\n";
}

echo "\n============================================================\n";
echo "  TRY THESE LOGINS NOW:\n";
echo "============================================================\n";
echo "  ADMIN:  admin / admin123\n";
echo "  HR:     hr / admin123  \n";
echo "  TEACHER: teacher1 / teacher123\n";
echo "  ZARA:   zara / zara123\n";
echo "============================================================\n";

