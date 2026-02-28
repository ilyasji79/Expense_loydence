<?php
/**
 * Logout Script
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Logout and redirect
logout($db);
redirect(BASE_URL . '/login.php', 'You have been logged out successfully', 'success');

