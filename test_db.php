<?php
/**
 * Test Database Connection
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "Testing Database Connection...<br>";
echo "DB Host: " . DB_HOST . "<br>";
echo "DB Name: " . DB_NAME . "<br>";
echo "DB User: " . DB_USER . "<br>";
echo "DB Pass: " . (empty(DB_PASS) ? "(empty)" : DB_PASS) . "<br><br>";

try {
    $result = $db->fetch("SELECT * FROM users WHERE username = 'admin'");
    if ($result) {
        echo "User found!<br>";
        echo "Username: " . $result['username'] . "<br>";
        echo "Email: " . $result['email'] . "<br>";
        echo "Password hash: " . $result['password'] . "<br><br>";
        
        // Test password
        $testPassword = 'admin123';
        if (password_verify($testPassword, $result['password'])) {
            echo "✓ Password 'admin123' is CORRECT!";
        } else {
            echo "✗ Password 'admin123' is INCORRECT!";
        }
    } else {
        echo "User not found!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

