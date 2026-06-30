<?php
// File: pages/account/login.php or pages/auth/login.php

require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) {
    if (isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/index.php');
    } else {
        header('Location: ' . SITE_URL . '/pages/shop.php');
    }
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Invalid security token. Please try again.'
        ];
        header('Location: login.php');
        exit();
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        if (!userLogin($email, $password)) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Invalid email or password.'
            ];
        } else {
            if ($remember) {
                $user = getUserByEmail($email);
                $token = generate_remember_token();
                set_remember_token($user['id'], $token);
                setcookie('remember_token', $user['id'] . '|' . $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }

            $user = getUserByEmail($email);

            if ($user['role'] === 'admin') {
                header('Location: ' . SITE_URL . '/admin/index.php');
            } else {
                header('Location: ' . SITE_URL . '/pages/shop.php');
            }
            exit();
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => implode(' ', $errors)
        ];
    }

    header('Location: login.php');
    exit();
}

$pageTitle = "Login - HomewareOnTap";
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    .auth-page {
        background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
        min-height: 70vh;
        padding: 70px 15px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .auth-card {
        width: 100%;
        max-width: 460px;
        background: #ffffff;
        border-radius: 22px;
        box-shadow: 0 18px 45px rgba(58, 50, 41, 0.12);
        overflow: hidden;
        border: 1px solid rgba(166, 123, 91, 0.12);
    }

    .auth-card-header {
        background: linear-gradient(135deg, #A67B5B 0%, #8B6145 100%);
        color: #ffffff;
        padding: 34px 34px 28px;
        text-align: center;
    }

    .auth-card-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
    }

    .auth-card-header p {
        margin: 8px 0 0;
        opacity: 0.9;
    }

    .auth-card-body {
        padding: 34px;
    }

    .auth-alert {
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #3A3229;
        display: block;
    }

    .form-control {
        height: 50px;
        border-radius: 12px;
        border: 1px solid #ddd4cc;
        padding: 12px 14px;
        font-size: 0.95rem;
    }

    .form-control:focus {
        border-color: #A67B5B;
        box-shadow: 0 0 0 0.2rem rgba(166, 123, 91, 0.18);
    }

    .remember-forgot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 8px 0 22px;
        font-size: 0.95rem;
    }

    .remember-me {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .forgot-password,
    .register-link a {
        color: #A67B5B;
        font-weight: 700;
        text-decoration: none;
    }

    .forgot-password:hover,
    .register-link a:hover {
        text-decoration: underline;
    }

    .btn-login {
        width: 100%;
        height: 52px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #A67B5B 0%, #8B6145 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.25s ease;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(166, 123, 91, 0.28);
    }

    .register-link {
        text-align: center;
        margin-top: 22px;
        color: #5f554c;
    }

    @media (max-width: 576px) {
        .auth-page {
            padding: 40px 12px;
        }

        .auth-card-body,
        .auth-card-header {
            padding-left: 24px;
            padding-right: 24px;
        }

        .remember-forgot {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<main class="auth-page">
    <div class="auth-card">
        <div class="auth-card-header">
            <h2>Welcome Back</h2>
            <p>Sign in to continue shopping</p>
        </div>

        <div class="auth-card-body">
            <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
                <div class="alert alert-success auth-alert">
                    You have been logged out successfully.
                </div>
            <?php endif; ?>

            <?php
            if (isset($_SESSION['flash_message'])) {
                $flash = $_SESSION['flash_message'];
                $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
                echo '<div class="alert alert-' . htmlspecialchars($type) . ' auth-alert">' . htmlspecialchars($flash['message']) . '</div>';
                unset($_SESSION['flash_message']);
            }
            ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email); ?>" 
                        placeholder="Enter your email"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required>
                </div>

                <div class="remember-forgot">
                    <label class="remember-me" for="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Remember me</span>
                    </label>

                    <a href="<?php echo SITE_URL; ?>/pages/account/forgot-password.php" class="forgot-password">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn-login">
                    Sign In
                </button>
            </form>

            <div class="register-link">
                Don't have an account?
                <a href="<?php echo SITE_URL; ?>/pages/account/register.php">Create one here</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
