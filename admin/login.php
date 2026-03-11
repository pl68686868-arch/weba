<?php declare(strict_types=1);

/**
 * Admin Login Page
 * 
 * Secure authentication with CSRF protection
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('/admin/dashboard.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    $ip = getClientIP();
    if (!checkRateLimit($ip, 'login', 5, 300)) { // 5 attempts per 5 minutes
        $error = 'Quá nhiều lần đăng nhập. Vui lòng thử lại sau 5 phút.';
    } else {
        // Validate CSRF token
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        
        if (!$auth->validateCSRFToken($token)) {
            $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Vui lòng nhập đầy đủ thông tin.';
            } else {
                // Attempt login
                if ($auth->login($username, $password)) {
                    // Check for redirect URL
                    $redirectUrl = $_SESSION['redirect_after_login'] ?? '/admin/dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirectUrl);
                } else {
                    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
                }
            }
        }
    }
}

// Generate new CSRF token for the form
$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Đăng nhập Admin | <?= SITE_NAME ?></title>
    <style>
        :root {
            --primary-color: #2C5F4F;
            --primary-hover: #3A7D6B;
            --bg-gradient: linear-gradient(135deg, #1a3a2f 0%, #2c5f4f 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --text-main: #1f2937;
            --text-muted: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, system-ui, sans-serif;
        }
        
        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-main);
        }
        
        .login-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 48px;
            width: 100%;
            max-width: 440px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--text-muted);
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(44, 95, 79, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: var(--primary-color);
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(44, 95, 79, 0.3);
        }
        
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 20px 25px -5px rgba(44, 95, 79, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fee2e2;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-color: #d1fae5;
        }
        
        .footer-text {
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 32px;
            opacity: 0.8;
        }

        /* Float animation for the container */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .login-container {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?= htmlspecialchars(SITE_NAME) ?></h1>
            <p>Quản trị nội dung</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= $auth->getCSRFInput() ?>
            
            <div class="form-group">
                <label for="username">Tên đăng nhập hoặc Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autofocus
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="btn">Đăng nhập</button>
        </form>
        
        <p class="footer-text">
            Bảo mật bằng CSRF & Rate Limiting
        </p>
    </div>
</body>
</html>
