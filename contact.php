<?php
declare(strict_types=1);

/**
 * Contact Page - Liên hệ
 * 
 * Contact form with AJAX submission and popup feedback
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

// ─── AJAX handler ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => ''];
    
    try {
        $ip = getClientIP();
        if (!checkRateLimit($ip, 'contact_form', 10, 3600)) {
            throw new \Exception('Bạn đã gửi quá nhiều tin nhắn. Vui lòng thử lại sau ít phút.');
        }

        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!$auth->validateCSRFToken($token)) {
            throw new \Exception('Phiên làm việc hết hạn. Vui lòng tải lại trang.');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            throw new \Exception('Vui lòng điền đầy đủ thông tin bắt buộc.');
        }
        if (!isValidEmail($email)) {
            throw new \Exception('Địa chỉ email không hợp lệ.');
        }
        if (strlen($message) < 20) {
            throw new \Exception('Tin nhắn quá ngắn. Vui lòng viết ít nhất 20 ký tự.');
        }

        // Store in database
        try {
            $db = Database::getInstance()->getPDO();
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, purpose, message, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $purpose, $message, $ip]);
        } catch (\Throwable $e) {
            error_log("Contact DB Error: " . $e->getMessage());
        }

        // Send emails (never crash)
        try {
            require_once __DIR__ . '/includes/Mailer.php';
            $mailer = new Mailer();
            $mailer->sendContactNotification($name, $email, $purpose, $message);
            $mailer->sendAutoReply($email, $name);
        } catch (\Throwable $e) {
            error_log("Contact Email Error: " . $e->getMessage());
        }

        error_log("Contact from: {$name} ({$email}) - Purpose: {$purpose}");

        $response['success'] = true;
        $response['message'] = 'Cảm ơn bạn! Lời chào đã được gửi thành công. Tôi sẽ phản hồi trong thời gian sớm nhất.';
    } catch (\Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Normal page render ─────────────────────────────────────────

// SEO
$seo = new SEO();
$seo->setTitle('Liên hệ')
    ->setDescription(get_setting('contact_meta_desc', 'Liên hệ để trao đổi về giảng dạy, đào tạo, hợp tác học thuật và những kết nối chuyên môn cùng quan tâm đến chiều sâu nội tâm và phát triển bền vững.'))
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
                    <?php 
                    $contactIntro = get_setting('contact_intro', '');
                    if ($contactIntro) {
                        foreach (explode("\n\n", $contactIntro) as $p) {
                            echo '<p>' . escape(trim($p)) . '</p>';
                        }
                    } else {
                    ?>
                    <p>Cảm ơn bạn đã ghé thăm. Tôi luôn trân trọng những cơ hội được lắng nghe 
                        và chia sẻ về hành trình thực hành tâm lý, giáo dục và chánh niệm.</p>
                    <p>Nếu bạn có lời mời hợp tác, thắc mắc chuyên môn, hoặc đơn giản là muốn gửi một lời chào, 
                        đừng ngần ngại để lại tin nhắn.</p>
                    <?php } ?>
                </div>
                
                <div class="contact-methods">
                    <div class="method-item">
                        <span class="method-label">Email trực tiếp</span>
                        <a href="mailto:doanduong1011@gmail.com" class="method-link link-underline">
                            doanduong1011@gmail.com
                        </a>
                    </div>
                    
                    <div class="method-item">
                        <span class="method-label">Thời gian phản hồi</span>
                        <p class="method-desc"><?= escape(get_setting('contact_response_time', 'Tôi thường kiểm tra email vào buổi sáng và sẽ phản hồi trong vòng 2-3 ngày làm việc.')) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Form -->
            <div class="contact-form-wrapper">
                <form id="contact-form" method="POST" action="" class="premium-form">
                    <?= $auth->getCSRFInput() ?>
                    
                    <div class="form-group floating">
                        <input type="text" id="name" name="name" class="form-input" placeholder=" " required>
                        <label for="name" class="form-label">Tên của bạn</label>
                    </div>
                    
                    <div class="form-group floating">
                        <input type="email" id="email" name="email" class="form-input" placeholder=" " required>
                        <label for="email" class="form-label">Địa chỉ Email</label>
                    </div>
                    
                    <div class="form-group">
                        <select id="purpose" name="purpose" class="form-select">
                            <option value="" disabled selected>Mục đích liên hệ</option>
                            <option value="teaching">Mời giảng dạy / Workshop</option>
                            <option value="collaboration">Hợp tác chuyên môn</option>
                            <option value="academic">Trao đổi học thuật</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    
                    <div class="form-group floating">
                        <textarea id="message" name="message" class="form-textarea" placeholder=" " required rows="5"></textarea>
                        <label for="message" class="form-label">Nội dung tin nhắn</label>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="contact-submit-btn">
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

<!-- Success/Error Popup Modal -->
<div id="contact-modal-overlay" class="contact-modal-overlay">
    <div class="contact-modal" id="contact-modal">
        <button class="contact-modal__close" id="contact-modal-close">&times;</button>
        <div class="contact-modal__icon" id="contact-modal-icon"></div>
        <h2 class="contact-modal__title" id="contact-modal-title"></h2>
        <p class="contact-modal__message" id="contact-modal-message"></p>
        <button class="contact-modal__btn" id="contact-modal-btn">Đã hiểu</button>
    </div>
</div>

<style>
/* Contact Modal */
.contact-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    animation: modalFadeIn 0.3s ease;
}
.contact-modal-overlay.active {
    display: flex;
}
.contact-modal {
    background: var(--color-surface, #2A2D2B);
    border: 1px solid rgba(236, 182, 19, 0.2);
    border-radius: 20px;
    padding: 3rem 2.5rem;
    max-width: 460px;
    width: 100%;
    text-align: center;
    position: relative;
    animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
}
.contact-modal__close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: none;
    border: none;
    color: var(--color-text-secondary, #999);
    font-size: 28px;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}
.contact-modal__close:hover {
    background: rgba(255,255,255,0.1);
    color: var(--color-text-primary, #fff);
}
.contact-modal__icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    line-height: 1;
}
.contact-modal__title {
    font-family: var(--font-heading, 'Georgia', serif);
    font-size: 1.75rem;
    font-weight: 500;
    color: var(--color-text-primary, #ECE8DF);
    margin: 0 0 1rem;
}
.contact-modal__message {
    font-family: var(--font-ui, -apple-system, sans-serif);
    font-size: 1rem;
    line-height: 1.7;
    color: var(--color-text-secondary, #999);
    margin: 0 0 2rem;
}
.contact-modal__btn {
    display: inline-block;
    padding: 14px 48px;
    background: var(--color-gold, #ECB613);
    color: var(--color-bg, #1C1F1D);
    border: none;
    border-radius: 50px;
    font-family: var(--font-ui, -apple-system, sans-serif);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.contact-modal__btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(236, 182, 19, 0.3);
}
.contact-modal.error .contact-modal__btn {
    background: rgba(255,255,255,0.15);
    color: var(--color-text-primary, #ECE8DF);
}

/* Loading state for submit button */
.btn-submit.loading {
    pointer-events: none;
    opacity: 0.7;
}
.btn-submit.loading .btn-submit-text {
    visibility: hidden;
}
.btn-submit.loading svg {
    display: none;
}
.btn-submit.loading::after {
    content: '';
    position: absolute;
    width: 22px;
    height: 22px;
    border: 2.5px solid rgba(28, 31, 29, 0.3);
    border-top-color: var(--color-bg, #1C1F1D);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const submitBtn = document.getElementById('contact-submit-btn');
    const overlay = document.getElementById('contact-modal-overlay');
    const modal = document.getElementById('contact-modal');
    const modalIcon = document.getElementById('contact-modal-icon');
    const modalTitle = document.getElementById('contact-modal-title');
    const modalMessage = document.getElementById('contact-modal-message');
    const modalBtn = document.getElementById('contact-modal-btn');
    const modalClose = document.getElementById('contact-modal-close');

    function showModal(success, message) {
        modal.className = 'contact-modal' + (success ? '' : ' error');
        modalIcon.textContent = success ? '✅' : '⚠️';
        modalTitle.textContent = success ? 'Gửi thành công!' : 'Có lỗi xảy ra';
        modalMessage.textContent = message;
        modalBtn.textContent = success ? 'Tuyệt vời!' : 'Thử lại';
        overlay.classList.add('active');
    }

    function closeModal() {
        overlay.classList.remove('active');
    }

    modalClose.addEventListener('click', closeModal);
    modalBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Loading state
        submitBtn.classList.add('loading');

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();
            showModal(data.success, data.message);

            if (data.success) {
                form.reset();
            }
        } catch (err) {
            showModal(false, 'Không thể kết nối đến máy chủ. Vui lòng thử lại.');
        } finally {
            submitBtn.classList.remove('loading');
        }
    });
});
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
