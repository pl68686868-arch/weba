<?php declare(strict_types=1);

/**
 * Subscription Confirmation Page
 * 
 * Handles the verification link from confirmation emails
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

$status = 'error';
$message = '';

$token = trim($_GET['token'] ?? '');
$email = trim($_GET['email'] ?? '');

if (!empty($token) && !empty($email)) {
    try {
        $db = Database::getInstance()->getPDO();
        
        $stmt = $db->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ? AND verification_token = ?");
        $stmt->execute([$email, $token]);
        $subscriber = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($subscriber) {
            if ($subscriber['status'] === 'active') {
                $status = 'already';
                $message = 'Email này đã được xác nhận trước đó rồi!';
            } else {
                $stmt = $db->prepare("UPDATE newsletter_subscribers SET status = 'active', confirmed_at = NOW(), verification_token = NULL WHERE id = ?");
                $stmt->execute([$subscriber['id']]);
                $status = 'success';
                $message = 'Đăng ký thành công! 🎉 Cảm ơn bạn đã tham gia cùng tôi.';
            }
        } else {
            $message = 'Link xác nhận không hợp lệ hoặc đã hết hạn.';
        }
    } catch (\Throwable $e) {
        error_log("Subscription confirmation error: " . $e->getMessage());
        $message = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
    }
} else {
    $message = 'Link xác nhận không hợp lệ.';
}

// SEO
$seo = new SEO();
$seo->setTitle('Xác nhận đăng ký')
    ->setCanonical(SITE_URL . '/confirm-subscription.php');

include __DIR__ . '/includes/header.php';
?>

<div class="contact-page" style="min-height: 60vh; display: flex; align-items: center;">
    <div class="container container--narrow" style="text-align: center; padding: 6rem 2rem;">
        <?php if ($status === 'success'): ?>
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">🎉</div>
            <h1 class="page-title" style="font-size: 2.5rem;">Chào mừng bạn!</h1>
            <p style="font-size: 1.25rem; color: var(--color-text-secondary); line-height: 1.8; margin: 1.5rem auto; max-width: 500px;">
                <?= htmlspecialchars($message) ?>
            </p>
            <p style="color: var(--color-text-secondary); margin-bottom: 2rem;">
                Bạn sẽ nhận được những bài viết mới nhất về tâm lý học, chánh niệm và hành trình phát triển bản thân.
            </p>
            <a href="/" class="btn btn-primary">Về trang chủ</a>
        <?php elseif ($status === 'already'): ?>
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">✅</div>
            <h1 class="page-title" style="font-size: 2.5rem;">Đã xác nhận</h1>
            <p style="font-size: 1.25rem; color: var(--color-text-secondary); line-height: 1.8; margin: 1.5rem auto; max-width: 500px;">
                <?= htmlspecialchars($message) ?>
            </p>
            <a href="/" class="btn btn-primary">Về trang chủ</a>
        <?php else: ?>
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">⚠️</div>
            <h1 class="page-title" style="font-size: 2.5rem;">Có lỗi xảy ra</h1>
            <p style="font-size: 1.25rem; color: var(--color-text-secondary); line-height: 1.8; margin: 1.5rem auto; max-width: 500px;">
                <?= htmlspecialchars($message) ?>
            </p>
            <a href="/contact.php" class="btn btn-outline">Liên hệ hỗ trợ</a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
