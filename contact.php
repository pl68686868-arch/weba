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
        $error = 'Bạn đã gửi quá nhiều tin nhắn. Vui lòng thử lại sau 1 giờ.';
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
    <div class="container">
        <div class="content-width">
            <header class="page-header">
                <h1>Liên hệ</h1>
                <p class="page-intro">
                    Dành cho các trao đổi về giảng dạy, đào tạo, hợp tác học thuật và những kết nối chuyên môn 
                    cùng quan tâm đến chiều sâu nội tâm và phát triển bền vững.
                </p>
            </header>
            
            <div class="contact-content">
                <!-- Contact Info -->
                <div class="contact-info">
                    <h2>Thông tin liên hệ</h2>
                    <div class="contact-item">
                        <strong>Email:</strong>
                        <a href="mailto:<?= htmlspecialchars(FROM_EMAIL) ?>">
                            <?= htmlspecialchars(FROM_EMAIL) ?>
                        </a>
                    </div>
                    <p class="contact-note">
                        Tôi thường phản hồi email trong vòng 2-3 ngày làm việc.
                    </p>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form-section">
                    <h2>Gửi tin nhắn</h2>
                    
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
                    
                    <form method="POST" action="" class="contact-form">
                        <?= $auth->getCSRFInput() ?>
                        
                        <div class="form-group">
                            <label for="name" class="form-label">Tên của bạn *</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-input"
                                required
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input"
                                required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose" class="form-label">Mục đích liên hệ</label>
                            <select id="purpose" name="purpose" class="form-select">
                                <option value="">-- Chọn mục đích --</option>
                                <option value="teaching" <?= ($_POST['purpose'] ?? '') === 'teaching' ? 'selected' : '' ?>>
                                    Mời giảng
                                </option>
                                <option value="collaboration" <?= ($_POST['purpose'] ?? '') === 'collaboration' ? 'selected' : '' ?>>
                                    Hợp tác
                                </option>
                                <option value="academic" <?= ($_POST['purpose'] ?? '') === 'academic' ? 'selected' : '' ?>>
                                    Trao đổi học thuật
                                </option>
                                <option value="other" <?= ($_POST['purpose'] ?? '') === 'other' ? 'selected' : '' ?>>
                                    Khác
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="message" class="form-label">Tin nhắn *</label>
                            <textarea 
                                id="message" 
                                name="message" 
                                class="form-textarea"
                                required
                                rows="8"
                                placeholder="Vui lòng chia sẻ chi tiết về nội dung bạn muốn trao đổi..."
                            ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            <small class="form-hint">Tối thiểu 20 ký tự</small>
                        </div>
                        
                        <div class="gdpr-notice">
                            <p>
                                <small>
                                    Bằng việc gửi form này, bạn đồng ý với việc thông tin cá nhân của bạn 
                                    được sử dụng để phản hồi yêu cầu của bạn. Thông tin sẽ không được chia sẻ 
                                    với bên thứ ba.
                                </small>
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            Gửi tin nhắn
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contact-page {
    padding: var(--space-4xl) 0;
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-5xl);
}

.contact-content {
    max-width: 700px;
    margin: 0 auto;
}

.contact-info {
    background: var(--color-bg-secondary);
    padding: var(--space-3xl);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-4xl);
}

.contact-info h2 {
    font-size: 1.5rem;
    margin-bottom: var(--space-lg);
}

.contact-item {
    margin-bottom: var(--space-md);
}

.contact-item strong {
    display: block;
    font-family: var(--font-ui);
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin-bottom: var(--space-xs);
}

.contact-item a {
    font-size: 1.125rem;
}

.contact-note {
    margin-top: var(--space-lg);
    font-size: 0.9375rem;
    color: var(--color-text-secondary);
    font-style: italic;
}

.contact-form-section h2 {
    font-size: 1.5rem;
    margin-bottom: var(--space-xl);
}

.form-hint {
    display: block;
    margin-top: var(--space-xs);
    font-family: var(--font-ui);
    font-size: 0.875rem;
    color: var(--color-text-tertiary);
}

.gdpr-notice {
    background: var(--color-bg-secondary);
    padding: var(--space-lg);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-xl);
}

.gdpr-notice p {
    margin: 0;
}

.alert {
    padding: var(--space-lg);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-xl);
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
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
