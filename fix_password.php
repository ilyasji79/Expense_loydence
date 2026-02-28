<?php
/**
 * Fix Password Hash
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

// Generate correct hash for "admin123"
$newHash = password_hash('admin123', PASSWORD_DEFAULT);

echo "New hash for 'admin123': " . $newHash . "<br><br>";

// Update admin user
$db->query("UPDATE users SET password = ? WHERE username = 'admin'", [$newHash]);
echo "Admin password updated!<br>";

// Update hr user  
$db->query("UPDATE users SET password = ? WHERE username = 'hr'", [$newHash]);
echo "HR password updated!<br><br>";

// Verify
$admin = $db->fetch("SELECT * FROM users WHERE username = 'admin'");
if (password_verify('admin123', $admin['password'])) {
    echo "✓ Password verification SUCCESSFUL!";
} else {
    echo "✗ Password verification FAILED!";
}

