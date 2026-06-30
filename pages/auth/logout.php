<?php
// includes/logout.php

// Start the correct session
session_name('HOT_SESSION');
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Remove session cookie
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

// Redirect to login page
header("Location: ../pages/auth/login.php?logout=success");
exit();
?>