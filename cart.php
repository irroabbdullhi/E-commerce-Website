<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['redirect_url'] = '/ecommerce-system/customer/cart/index.php';
    header("Location: /ecommerce-system/auth/login.php");
    exit;
}

// Redirect based on role
if ($_SESSION['user_role'] === 'customer') {
    header("Location: /ecommerce-system/customer/cart/index.php");
    exit;
} else {
    // Non-customers shouldn't access a shopping cart
    header("Location: /ecommerce-system/index.php");
    exit;
}
?>
