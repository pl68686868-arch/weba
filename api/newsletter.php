<?php
declare(strict_types=1);

/**
 * Newsletter API - Subscribe endpoint
 * 
 * Handle newsletter subscription requests
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// CORS headers if needed
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Rate limiting
$ip = getClientIP();
if (!checkRateLimit($ip, 'newsletter_subscribe', 3, 3600)) {
    jsonResponse(['success' => false, 'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'], 429);
}

// Get JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

// Validation
if (empty($email)) {
    jsonResponse(['success' => false, 'message' => 'Email là bắt buộc.'], 400);
}

if (!isValidEmail($email)) {
    jsonResponse(['success' => false, 'message' => 'Email không hợp lệ.'], 400);
}

try {
    $db = Database::getInstance();
    
    // Check if already subscribed
    $existing = $db->fetchOne(
        "SELECT id, status FROM newsletter_subscribers WHERE email = :email LIMIT 1",
        ['email' => $email]
    );
    
    if ($existing) {
        if ($existing['status'] === 'active') {
            jsonResponse([
                'success' => false, 
                'message' => 'Email này đã đăng ký newsletter.'
            ], 400);
        } else {
            // Reactivate
            $db->update(
                'newsletter_subscribers',
                ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $existing['id']]
            );
            
            jsonResponse([
                'success' => true,
                'message' => 'Đăng ký thành công! Cảm ơn bạn đã đăng ký newsletter.'
            ]);
        }
    }
    
    // Generate confirmation token
    $token = bin2hex(random_bytes(32));
    
    // Insert new subscriber
    $subscriberId = $db->insert('newsletter_subscribers', [
        'email' => $email,
        'status' => 'active', // In production, set to 'pending' and send confirmation email
        'confirmation_token' => $token,
        'ip_address' => $ip
    ]);
    
    if ($subscriberId) {
        // In production, send confirmation email here
        
        jsonResponse([
            'success' => true,
            'message' => 'Cảm ơn bạn đã đăng ký! Vui lòng kiểm tra email để xác nhận.'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log('Newsletter subscribe error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.'
    ], 500);
}
