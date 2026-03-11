<?php declare(strict_types=1);

/**
 * Newsletter Subscription Handler
 * 
 * Handles subscription form submissions (AJAX endpoint)
 * Implements double opt-in flow
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Mailer.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \Exception('Invalid request method.');
    }

    // Rate limiting
    $ip = getClientIP();
    if (!checkRateLimit($ip, 'newsletter_subscribe', 5, 3600)) {
        throw new \Exception('Bạn đã thử quá nhiều lần. Vui lòng thử lại sau.');
    }

    // Validate CSRF
    $auth = new Auth();
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!$auth->validateCSRFToken($token)) {
        throw new \Exception('Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
    }

    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['subscriber_name'] ?? '');

    if (empty($email) || !isValidEmail($email)) {
        throw new \Exception('Vui lòng nhập địa chỉ email hợp lệ.');
    }

    $db = Database::getInstance()->getPDO();

    // Check if already subscribed
    $stmt = $db->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['status'] === 'active') {
            throw new \Exception('Email này đã được đăng ký rồi! 🎉');
        }
        if ($existing['status'] === 'unconfirmed') {
            // Resend confirmation
            $verifyToken = bin2hex(random_bytes(32));
            $stmt = $db->prepare("UPDATE newsletter_subscribers SET verification_token = ?, name = COALESCE(NULLIF(?, ''), name) WHERE id = ?");
            $stmt->execute([$verifyToken, $name, $existing['id']]);

            $mailer = new Mailer();
            $mailer->sendSubscriptionConfirmation($email, $verifyToken, $name ?: null);

            $response['success'] = true;
            $response['message'] = 'Email xác nhận đã được gửi lại. Vui lòng kiểm tra hộp thư của bạn!';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($existing['status'] === 'unsubscribed') {
            // Re-subscribe
            $verifyToken = bin2hex(random_bytes(32));
            $stmt = $db->prepare("UPDATE newsletter_subscribers SET status = 'unconfirmed', verification_token = ?, name = COALESCE(NULLIF(?, ''), name) WHERE id = ?");
            $stmt->execute([$verifyToken, $name, $existing['id']]);

            $mailer = new Mailer();
            $mailer->sendSubscriptionConfirmation($email, $verifyToken, $name ?: null);

            $response['success'] = true;
            $response['message'] = 'Chào mừng bạn quay lại! Vui lòng xác nhận đăng ký qua email.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // New subscription
    $verifyToken = bin2hex(random_bytes(32));
    $stmt = $db->prepare("INSERT INTO newsletter_subscribers (email, name, status, verification_token) VALUES (?, ?, 'unconfirmed', ?)");
    $stmt->execute([$email, $name ?: null, $verifyToken]);

    // Send confirmation email
    $mailer = new Mailer();
    $mailer->sendSubscriptionConfirmation($email, $verifyToken, $name ?: null);

    $response['success'] = true;
    $response['message'] = 'Cảm ơn bạn! Vui lòng kiểm tra email để xác nhận đăng ký. 📧';

} catch (\Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
