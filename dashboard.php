<?php
/**
 * Dashboard Redirect
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect based on role
if (hasRole($db, 'admin')) {
    redirect(BASE_URL . '/admin/dashboard.php');
} elseif (hasRole($db, 'hr_manager')) {
    redirect(BASE_URL . '/hr/dashboard.php');
} else {
    // Default redirect for other roles
    redirect(BASE_URL . '/login.php');
}

