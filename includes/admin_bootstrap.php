<?php
// includes/admin_bootstrap.php

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Check admin login
if (!isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit();
}
?>