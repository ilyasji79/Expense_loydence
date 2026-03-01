<?php
/**
 * Database Update Script
 * Adds status column to users table for employee approval workflow
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "Starting database updates...\n";

try {
    // Check if status column exists in users table
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $columnExists = $result->fetch();
    
    if (!$columnExists) {
        echo "Adding 'status' column to users table...\n";
        $db->query("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER is_active");
        echo "Status column added successfully!\n";
        
        // Update existing users to approved
        $db->query("UPDATE users SET status = 'approved' WHERE is_active = 1");
        echo "Existing users updated to 'approved' status.\n";
    } else {
        echo "Status column already exists.\n";
    }
    
    // Check if photo column exists in employees table
    $result = $db->query("SHOW COLUMNS FROM employees LIKE 'photo'");
    $photoColumnExists = $result->fetch();
    
    if (!$photoColumnExists) {
        echo "Adding 'photo' column to employees table...\n";
        $db->query("ALTER TABLE employees ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER emergency_contact_phone");
        echo "Photo column added successfully!\n";
    } else {
        echo "Photo column already exists.\n";
    }
    
    // Create uploads directory for employee photos
    $uploadDir = __DIR__ . '/uploads/employee_photos';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "Created uploads directory for employee photos.\n";
    }
    
    // Update existing employees without leave balances
    $employees = $db->fetchAll("SELECT id FROM employees WHERE is_active = 1");
    $leaveTypes = $db->fetchAll("SELECT id, days_per_year FROM employee_leave_types WHERE is_active = 1 AND days_per_year > 0");
    
    foreach ($employees as $emp) {
        foreach ($leaveTypes as $lt) {
            $existing = $db->fetch("SELECT id FROM employee_leave_balance WHERE employee_id = ? AND leave_type_id = ?", 
                [$emp['id'], $lt['id']]);
            
            if (!$existing) {
                $db->query("INSERT INTO employee_leave_balance (employee_id, leave_type_id, year, total_days, used_days, remaining_days) 
                    VALUES (?, ?, ?, ?, 0, ?)", 
                    [$emp['id'], $lt['id'], date('Y'), $lt['days_per_year'], $lt['days_per_year']]);
            }
        }
    }
    echo "Leave balances created for employees.\n";
    
    echo "\n=== Database updates completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

