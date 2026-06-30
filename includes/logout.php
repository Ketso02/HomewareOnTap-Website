<?php
// includes/logout.php

require_once __DIR__ . '/config.php';

// Use the same session name as login
session_name('HOT_SESSION');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to the correct formatted login page
$redirect_url = SITE_URL . '/pages/account/login.php?logout=success';

if (isset($_GET['admin']) && $_GET['admin'] == '1') {
    $redirect_url = SITE_URL . '/admin/index.php?logout=success';
}

header('Location: ' . $redirect_url);
exit();
?>