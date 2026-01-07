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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #2C5F4F 0%, #3A7D6B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        
        .login-container {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            color: #2C5F4F;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #5A5A5A;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            color: #2D2D2D;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E3DD;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3A7D6B;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #2C5F4F;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #3A7D6B;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #FEE;
            color: #C33;
            border: 1px solid #FCC;
        }
        
        .alert-success {
            background: #EFE;
            color: #3A3;
            border: 1px solid #CFC;
        }
        
        .footer-text {
            text-align: center;
            color: #5A5A5A;
            font-size: 13px;
            margin-top: 32px;
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
