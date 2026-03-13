<?php
/**
 * Authentication and Authorization
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login to access page
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

/**
 * Check if user has specific role
 */
function has_role($roles) {
    if (!isset($_SESSION['role'])) return false;
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }
    return $_SESSION['role'] === $roles;
}

/**
 * Require specific role to access page
 */
function require_role($roles) {
    require_login();
    if (!has_role($roles)) {
        header("Location: " . BASE_URL . "dashboard.php?error=unauthorized");
        exit();
    }
}

/**
 * Login user
 */
function login_user($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            return "Your account is deactivated. Please contact admin.";
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['avatar'] = $user['avatar'];
        $_SESSION['pharmacy_id'] = $user['pharmacy_id'];

        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);

        return true;
    }

    return "Invalid username or password.";
}

/**
 * Logout user
 */
function logout_user() {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "login.php");
    exit();
}
