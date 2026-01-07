<?php
declare(strict_types=1);

/**
 * Contact Page - Liên hệ
 * 
 * Contact form with rate limiting and validation
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    $ip = getClientIP();
    if (!checkRateLimit($ip, 'contact_form', 3, 3600)) { // 3 messages per hour
        $error = 'Cảm ơn bạn đã quan tâm. Để tôi có thể phản hồi tốt nhất, xin vui lòng đợi một chút trước khi gửi tin nhắn tiếp nhé.';
    } else {
        // Validate CSRF token
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        
        if (!$auth->validateCSRFToken($token)) {
            $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
        } else {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $purpose = trim($_POST['purpose'] ?? '');
            $message = trim($_POST['message'] ?? '');
            
            // Validation
            if (empty($name) || empty($email) || empty($message)) {
                $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
            } elseif (!isValidEmail($email)) {
                $error = 'Địa chỉ email không hợp lệ.';
            } elseif (strlen($message) < 20) {
                $error = 'Tin nhắn quá ngắn. Vui lòng viết ít nhất 20 ký tự.';
            } else {
                // In a real application, you would:
                // 1. Send email to FROM_EMAIL
                // 2. Or store in database for admin review
                
                // For now, just show success
                $success = 'Cảm ơn bạn đã liên hệ! Tôi sẽ phản hồi trong thời gian sớm nhất.';
                
                // Log the contact
                error_log("Contact from: {$name} ({$email}) - Purpose: {$purpose}");
                
                // Clear form
                $_POST = [];
            }
        }
    }
}

// SEO Setup
$seo = new SEO();
$seo->setTitle('Liên hệ')
    ->setDescription('Liên hệ để trao đổi về giảng dạy, đào tạo, hợp tác học thuật và những kết nối chuyên môn cùng quan tâm đến chiều sâu nội tâm và phát triển bền vững.')
    ->setCanonical(SITE_URL . '/contact.php')
    ->setOGType('website')
    ->setOGImage(DEFAULT_OG_IMAGE);

// Track page view  
trackPageView(null, '/contact.php');

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="contact-page">
    <div class="container container--narrow">
        <div class="contact-grid">
            <!-- Left Column: Visual & Info -->
            <div class="contact-info">
                <h1 class="page-title">Kết nối &<br>Trò chuyện</h1>
                <div class="contact-intro">
                    <p>
                        Cảm ơn bạn đã ghé thăm. Tôi luôn trân trọng những cơ hội được lắng nghe 
                        và chia sẻ về hành trình thực hành tâm lý, giáo dục và chánh niệm.
                    </p>
                    <p>
                        Nếu bạn có lời mời hợp tác, thắc mắc chuyên môn, hoặc đơn giản là muốn gửi một lời chào, 
                        đừng ngần ngại để lại tin nhắn.
                    </p>
                </div>
                
                <div class="contact-methods">
                    <div class="method-item">
                        <span class="method-label">Email trực tiếp</span>
                        <a href="mailto:<?= htmlspecialchars(FROM_EMAIL) ?>" class="method-link link-underline">
                            <?= htmlspecialchars(FROM_EMAIL) ?>
                        </a>
                    </div>
                    
                    <div class="method-item">
                        <span class="method-label">Thời gian phản hồi</span>
                        <p class="method-desc">Tôi thường kiểm tra email vào buổi sáng và sẽ phản hồi trong vòng 2-3 ngày làm việc.</p>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Form -->
            <div class="contact-form-wrapper">
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
                
                <form method="POST" action="" class="premium-form">
                    <?= $auth->getCSRFInput() ?>
                    
                    <div class="form-group floating">
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input"
                            placeholder=" "
                            required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        >
                        <label for="name" class="form-label">Tên của bạn</label>
                    </div>
                    
                    <div class="form-group floating">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input"
                            placeholder=" "
                            required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >
                        <label for="email" class="form-label">Địa chỉ Email</label>
                    </div>
                    
                    <div class="form-group">
                        <select id="purpose" name="purpose" class="form-select">
                            <option value="" disabled selected>Mục đích liên hệ</option>
                            <option value="teaching" <?= ($_POST['purpose'] ?? '') === 'teaching' ? 'selected' : '' ?>>Mời giảng dạy / Workshop</option>
                            <option value="collaboration" <?= ($_POST['purpose'] ?? '') === 'collaboration' ? 'selected' : '' ?>>Hợp tác chuyên môn</option>
                            <option value="academic" <?= ($_POST['purpose'] ?? '') === 'academic' ? 'selected' : '' ?>>Trao đổi học thuật</option>
                            <option value="other" <?= ($_POST['purpose'] ?? '') === 'other' ? 'selected' : '' ?>>Khác</option>
                        </select>
                    </div>
                    
                    <div class="form-group floating">
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-textarea"
                            placeholder=" "
                            required
                            rows="5"
                        ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        <label for="message" class="form-label">Nội dung tin nhắn</label>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <span class="btn-submit-text">Gửi lời chào</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                    
                    <p class="form-privacy">
                        Thông tin của bạn được bảo mật an toàn.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
