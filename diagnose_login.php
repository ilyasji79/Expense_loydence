<?php
/**
 * Diagnostic & Fix Script - Login Issues
 * Run this to check and fix login problems
 */

echo "============================================================\n";
echo "  LOGIN DIAGNOSTIC TOOL\n";
echo "  Expense Management ERP - Loydence Academy\n";
echo "============================================================\n\n";

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "[1] Checking database connection...\n";

$db = new Database();

// ============================================================
// CHECK USERS TABLE
// ============================================================
echo "\n[2] Checking users table...\n";
$users = $db->fetchAll("SELECT id, username, email, role_id, is_active FROM users ORDER BY id");

echo "  Found " . count($users) . " users:\n";
foreach ($users as $u) {
    $role = $db->fetch("SELECT role_name FROM roles WHERE id = ?", [$u['role_id']]);
    $status = $u['is_active'] ? 'ACTIVE' : 'INACTIVE';
    echo "    - ID: {$u['id']}, Username: {$u['username']}, Role: {$role['role_name']}, Status: $status\n";
}

// ============================================================
// CHECK ROLES
// ============================================================
echo "\n[3] Checking roles...\n";
$roles = $db->fetchAll("SELECT * FROM roles");
echo "  Found " . count($roles) . " roles:\n";
foreach ($roles as $r) {
    echo "    - ID: {$r['id']}, Name: {$r['role_name']}\n";
}

// ============================================================
// CHECK EMPLOYEES TABLE
// ============================================================
echo "\n[4] Checking employees table...\n";
$employees = $db->fetchAll("SELECT id, employee_code, full_name, email, user_id, is_active FROM employees ORDER BY id");

if (count($employees) > 0) {
    echo "  Found " . count($employees) . " employees:\n";
    foreach ($employees as $e) {
        $hasUser = $e['user_id'] ? 'YES (User ID: ' . $e['user_id'] . ')' : 'NO';
        $status = $e['is_active'] ? 'ACTIVE' : 'INACTIVE';
        echo "    - ID: {$e['id']}, Code: {$e['employee_code']}, Name: {$e['full_name']}, Has User: $hasUser, Status: $status\n";
    }
} else {
    echo "  No employees found!\n";
}

// ============================================================
// TEST PASSWORD HASH
// ============================================================
echo "\n[5] Testing password hashes...\n";
$testPassword = 'admin123';

foreach ($users as $u) {
    $fullUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$u['id']]);
    $verify = password_verify($testPassword, $fullUser['password']);
    echo "    - {$u['username']}: " . ($verify ? "✓ Password works!" : "✗ PASSWORD DOES NOT MATCH!") . "\n";
}

// ============================================================
// FIX PASSWORD HASHES IF NEEDED
// ============================================================
echo "\n[6] Checking if passwords need fixing...\n";
$needsFix = false;
foreach ($users as $u) {
    $fullUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$u['id']]);
    if (!password_verify($testPassword, $fullUser['password'])) {
        $needsFix = true;
    }
}

if ($needsFix) {
    echo "  ⚠️  Passwords need to be fixed!\n";
    echo "  Do you want to fix them? (Type 'yes' and press Enter): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'yes') {
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        
        foreach ($users as $u) {
            $db->query("UPDATE users SET password = ? WHERE id = ?", [$newHash, $u['id']]);
            echo "  ✓ Fixed password for: {$u['username']}\n";
        }
        echo "\n  All passwords have been reset to: $testPassword\n";
    }
} else {
    echo "  ✓ All passwords are working correctly!\n";
}

// ============================================================
// CREATE EMPLOYEE USER ACCOUNT IF NEEDED
// ============================================================
echo "\n[7] Checking for employees without user accounts...\n";

$employeesWithoutUsers = $db->fetchAll("
    SELECT e.* FROM employees e 
    WHERE e.is_active = 1 
    AND (e.user_id IS NULL OR e.user_id = 0)
    ORDER BY e.full_name
");

if (count($employeesWithoutUsers) > 0) {
    echo "  Found " . count($employeesWithoutUsers) . " employees without user accounts:\n";
    foreach ($employeesWithoutUsers as $emp) {
        echo "    - {$emp['full_name']} ({$emp['employee_code']})\n";
    }
    
    echo "\n  Do you want to create user accounts for these employees? (Type 'yes' and press Enter): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'yes') {
        // Get employee role
        $employeeRole = $db->fetch("SELECT id FROM roles WHERE role_name = 'employee'");
        
        if (!$employeeRole) {
            echo "  ✗ Employee role not found! Creating it...\n";
            $db->query("INSERT INTO roles (role_name, role_description) VALUES ('employee', 'Employee - View personal dashboard, attendance, salary, request leave')");
            $employeeRole = $db->fetch("SELECT id FROM roles WHERE role_name = 'employee'");
        }
        
        foreach ($employeesWithoutUsers as $emp) {
            // Generate username from first name + last name
            $username = strtolower($emp['first_name'] . $emp['last_name']);
            $username = preg_replace('/[^a-z0-9]/', '', $username);
            
            // Check if username exists
            $existing = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existing) {
                $username = $username . $emp['id'];
            }
            
            // Default password (employee should change it)
            $defaultPassword = 'employee123';
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            try {
                $db->query("
                    INSERT INTO users (username, password, email, full_name, role_id, phone, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ", [
                    $username,
                    $hashedPassword,
                    $emp['email'],
                    $emp['full_name'],
                    $employeeRole['id'],
                    $emp['phone']
                ]);
                
                $userId = $db->lastInsertId();
                
                // Link employee to user
                $db->query("UPDATE employees SET user_id = ? WHERE id = ?", [$userId, $emp['id']]);
                
                echo "  ✓ Created user account for {$emp['full_name']}: $username / $defaultPassword\n";
            } catch (Exception $e) {
                echo "  ✗ Error creating user for {$emp['full_name']}: " . $e->getMessage() . "\n";
            }
        }
    }
} else {
    echo "  ✓ All active employees have user accounts!\n";
}

// ============================================================
// SUMMARY
// ============================================================
echo "\n============================================================\n";
echo "  DIAGNOSTIC COMPLETE\n";
echo "============================================================\n\n";

echo "Default Login Credentials:\n";
echo "  Admin:  admin / admin123\n";
echo "  HR:     hr / admin123\n";
echo "  Employee: (check above)\n\n";

echo "To test, try logging in at:\n";
echo "  - Admin/HR: " . BASE_URL . "/login.php\n";
echo "  - Employee: " . BASE_URL . "/employee/login.php\n";
echo "\n";

