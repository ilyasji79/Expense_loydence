<?php
/**
 * Database Migration - Add User Status Column
 * Run this file once to add the status column to users table
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "Starting database migration...\n";

// Check if status column exists
$columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'status'");

if (empty($columns)) {
    echo "Adding 'status' column to users table...\n";
    
    // Add status column with default 'approved' for existing users
    $db->query("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER is_active");
    
    // Update existing admin and HR users to 'approved' (they should already be approved)
    $db->query("UPDATE users SET status = 'approved' WHERE role_id IN (1, 2)"); // admin and hr_manager
    
    echo "Status column added successfully!\n";
} else {
    echo "Status column already exists!\n";
}

// Verify the migration
echo "\nVerifying users table structure:\n";
$users = $db->fetchAll("SELECT id, username, role_id, status, is_active FROM users LIMIT 10");
foreach ($users as $user) {
    echo "- User: {$user['username']}, Role: {$user['role_id']}, Status: {$user['status']}, Active: {$user['is_active']}\n";
}

echo "\nMigration completed successfully!\n";

