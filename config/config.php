<?php
/**
 * Database Configuration
 * Expense Management ERP - Loydence Academy
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'expense_erp');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application constants
define('SITE_NAME', 'Loydence Academy British School');
define('SITE_LOCATION', 'Al Aziziyah, Qatar');
define('ADMIN_NAME', 'Mr. Mohammad Ilyas');
define('HR_NAME', 'Sharifa Shaikh');
define('CURRENCY', 'QAR');
define('WARNING_BALANCE', 1000);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Timezone
date_default_timezone_set('Asia/Qatar');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
define('BASE_URL', $protocol . '://' . $_SERVER['HTTP_HOST'] . '/Expense_loydence');

// Upload configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('INVOICE_PATH', UPLOAD_PATH . 'invoices/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

