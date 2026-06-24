<?php
// Secure Session Management
if (session_status() == PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/ecommerce-system/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Session hijacking/fixation countermeasure
if (!isset($_SESSION['created_ip'])) {
    $_SESSION['created_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} else {
    if ($_SESSION['created_ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        session_start();
    }
}

// CSRF Protection Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_csrf_token() {
    return $_SESSION['csrf_token'];
}

// Role-Based Access Control Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_logged_in_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'business_id' => $_SESSION['business_id'] ?? null
    ];
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: /ecommerce-system/auth/login.php");
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (is_string($roles)) {
        $roles = [$roles];
    }
    if (!in_array($_SESSION['user_role'], $roles)) {
        // Forbidden page or redirect to their respective dashboard
        header("HTTP/1.1 403 Forbidden");
        echo "403 Forbidden - You do not have permission to access this page.";
        exit;
    }
}

function redirect_dashboard() {
    if (is_logged_in()) {
        switch ($_SESSION['user_role']) {
            case 'admin':
                header("Location: /ecommerce-system/admin/dashboard.php");
                break;
            case 'owner':
                header("Location: /ecommerce-system/owner/dashboard.php");
                break;
            case 'customer':
                header("Location: /ecommerce-system/customer/dashboard.php");
                break;
        }
        exit;
    }
}
?>
