<?php
/**
 * Authentication Functions
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/functions.php';

// Login user
function login($db, $username, $password) {
    $user = $db->fetch("SELECT * FROM users WHERE username = ? AND is_active = 1", [$username]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Check user status (for employee accounts)
    if (isset($user['status']) && $user['status'] === 'pending') {
        return ['success' => false, 'message' => 'Your account is pending approval. Please contact HR.'];
    }
    
    if (isset($user['status']) && $user['status'] === 'rejected') {
        return ['success' => false, 'message' => 'Your account has been rejected. Please contact HR.'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['login_time'] = time();
    
    // Get role name
    $role = $db->fetch("SELECT role_name FROM roles WHERE id = ?", [$user['role_id']]);
    $_SESSION['role_name'] = $role['role_name'];
    
    // Log activity
    logActivity($db, $user['id'], 'user_login', "User logged in: " . $user['username']);
    
    return ['success' => true, 'message' => 'Login successful'];
}

// Logout user
function logout($db) {
    if (isset($_SESSION['user_id'])) {
        logActivity($db, $_SESSION['user_id'], 'user_logout', "User logged out: " . $_SESSION['username']);
    }
    
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
}

// Check session validity (timeout after 2 hours)
function checkSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $timeout = 7200; // 2 hours
    if (time() - $_SESSION['login_time'] > $timeout) {
        logout($GLOBALS['db']);
        return false;
    }
    
    return true;
}

// Require login
function requireLogin() {
    if (!checkSession()) {
        redirect(BASE_URL . '/login.php', 'Please login to continue', 'error');
    }
}

// Require admin role
function requireAdmin($db) {
    requireLogin();
    if (!hasRole($db, 'admin')) {
        redirect(BASE_URL . '/dashboard.php', 'Access denied. Admin only.', 'error');
    }
}

// Require HR role
function requireHR($db) {
    requireLogin();
    if (!hasRole($db, 'hr_manager')) {
        redirect(BASE_URL . '/dashboard.php', 'Access denied. HR Manager only.', 'error');
    }
}

// Check if admin
function isAdmin($db) {
    return hasRole($db, 'admin');
}

// Check if HR manager
function isHR($db) {
    return hasRole($db, 'hr_manager');
}

// Change password
function changePassword($db, $userId, $currentPassword, $newPassword) {
    $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
    
    logActivity($db, $userId, 'password_changed', "Password changed successfully");
    
    return ['success' => true, 'message' => 'Password changed successfully'];
}

// Create user (admin only)
function createUser($db, $username, $email, $password, $fullName, $roleId) {
    // Check if username exists
    $existing = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email exists
    $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $db->query("INSERT INTO users (username, password, email, full_name, role_id) VALUES (?, ?, ?, ?, ?)",
        [$username, $hashedPassword, $email, $fullName, $roleId]);
    
    $userId = $db->lastInsertId();
    logActivity($db, $_SESSION['user_id'], 'user_created', "New user created: $username (ID: $userId)");
    
    return ['success' => true, 'message' => 'User created successfully'];
}

// Update user
function updateUser($db, $userId, $email, $fullName, $roleId) {
    $db->query("UPDATE users SET email = ?, full_name = ?, role_id = ? WHERE id = ?",
        [$email, $fullName, $roleId, $userId]);
    
    logActivity($db, $_SESSION['user_id'], 'user_updated', "User updated: ID $userId");
    
    return ['success' => true, 'message' => 'User updated successfully'];
}

// Delete user (soft delete - set inactive)
function deleteUser($db, $userId) {
    if ($userId == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'You cannot delete your own account'];
    }
    
    $db->query("UPDATE users SET is_active = 0 WHERE id = ?", [$userId]);
    
    logActivity($db, $_SESSION['user_id'], 'user_deleted', "User deleted: ID $userId");
    
    return ['success' => true, 'message' => 'User deleted successfully'];
}

// Get all users
function getAllUsers($db) {
    return $db->fetchAll("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        ORDER BY u.is_active DESC, u.full_name ASC
    ");
}

