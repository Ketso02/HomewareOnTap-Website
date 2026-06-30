<?php
// login-process.php - FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session without session.php interference for processing
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOT_SESSION');
    session_start();
}

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Debug logging
error_log("=== LOGIN PROCESS STARTED ===");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid security token. Please refresh the page.";
        error_log("CSRF token validation failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember_me']);

    error_log("Login attempt for email: $email");

    // Basic validation
    if (empty($email)) {
        $_SESSION['error'] = "Please enter an email address.";
        error_log("Empty email validation failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }

    // Create database connection
    $database = new Database();
    $db_connection = $database->getConnection();
    
    if (!$db_connection) {
        $_SESSION['error'] = "Database connection failed. Please try again.";
        error_log("Database connection failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }

    try {
        // Get user by email WITH password verification
        $user = $database->fetchSingle(
            "SELECT * FROM users WHERE email = ? AND status = 1",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            error_log("User authenticated successfully: " . $user['email']);
            
            // Set consistent session format
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? '',
                'created_at' => $user['created_at']
            ];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Update last login timestamp
            $database->executeQuery(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            error_log("Session set for user: " . $user['email'] . " | role: " . $user['role']);
            
            // Redirect based on role or intended destination
            if ($_SESSION['user_role'] === 'admin') {
                header('Location: ' . SITE_URL . '/admin/index.php');
            } else {
                $redirect = $_SESSION['redirect_after_login'] ?? '';
                unset($_SESSION['redirect_after_login']);
                if (!empty($redirect)) {
                    header('Location: ' . $redirect);
                } else {
                    header('Location: ' . SITE_URL . '/pages/account/dashboard.php');
                }
            }
            exit;
            
        } else {
            // Invalid credentials — send back to login with an error message
            error_log("Login failed for email: $email (user not found or wrong password)");
            $_SESSION['error'] = "Invalid email address or password. Please try again.";
            // Preserve redirect destination so it survives the failed login attempt
            // (redirect_after_login is already set in session — don't unset it)
            header('Location: ' . SITE_URL . '/pages/auth/login.php');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred. Please try again.";
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
    
} else {
    error_log("Invalid request method");
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

error_log("=== LOGIN PROCESS COMPLETED ===");
?>