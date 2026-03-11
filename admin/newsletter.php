<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

$subscribers = $db->fetchAll("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
$sendResult = null;
$sendError = null;

// Handle newsletter send
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!$auth->validateCSRFToken($token)) {
        $sendError = 'Phiên làm việc hết hạn. Vui lòng tải lại trang.';
    } else {
        if ($action === 'send_test') {
            // Send test email to admin
            $subject = trim($_POST['subject'] ?? '');
            $content = trim($_POST['content'] ?? '');
            
            if (empty($subject) || empty($content)) {
                $sendError = 'Vui lòng nhập tiêu đề và nội dung.';
            } else {
                try {
                    $mailer = new Mailer();
                    $result = $mailer->sendNewsletter('doanduong1011@gmail.com', $subject, $content);
                    if ($result) {
                        $sendResult = 'Email thử nghiệm đã được gửi đến doanduong1011@gmail.com!';
                    } else {
                        $sendError = 'Không thể gửi email. Kiểm tra cấu hình SMTP.';
                    }
                } catch (\Throwable $e) {
                    $sendError = 'Lỗi: ' . $e->getMessage();
                    error_log("Admin Newsletter Send Error: " . $e->getMessage());
                }
            }
        } elseif ($action === 'send_all') {
            // Send to all active subscribers
            $subject = trim($_POST['subject'] ?? '');
            $content = trim($_POST['content'] ?? '');
            
            if (empty($subject) || empty($content)) {
                $sendError = 'Vui lòng nhập tiêu đề và nội dung.';
            } else {
                $activeSubscribers = $db->fetchAll("SELECT email FROM newsletter_subscribers WHERE status = 'active'");
                
                if (empty($activeSubscribers)) {
                    $sendError = 'Không có subscriber nào đã xác nhận. Chỉ gửi được cho subscriber có status "active".';
                } else {
                    $mailer = new Mailer();
                    $sent = 0;
                    $failed = 0;
                    
                    foreach ($activeSubscribers as $sub) {
                        try {
                            $result = $mailer->sendNewsletter($sub['email'], $subject, $content);
                            if ($result) $sent++;
                            else $failed++;
                        } catch (\Throwable $e) {
                            $failed++;
                            error_log("Newsletter send failed for {$sub['email']}: " . $e->getMessage());
                        }
                    }
                    
                    $sendResult = "Đã gửi thành công {$sent}/" . count($activeSubscribers) . " email." . ($failed > 0 ? " ({$failed} thất bại)" : '');
                }
            }
        } elseif ($action === 'delete_subscriber') {
            $subId = (int)($_POST['subscriber_id'] ?? 0);
            if ($subId > 0) {
                $db->query("DELETE FROM newsletter_subscribers WHERE id = ?", [$subId]);
                $sendResult = 'Đã xóa subscriber.';
                // Re-fetch
                $subscribers = $db->fetchAll("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
            }
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header">
    <h1>Newsletter</h1>
</div>

<?php if ($sendResult): ?>
    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
        ✅ <?= htmlspecialchars($sendResult) ?>
    </div>
<?php endif; ?>

<?php if ($sendError): ?>
    <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
        ⚠️ <?= htmlspecialchars($sendError) ?>
    </div>
<?php endif; ?>

<div class="grid-layout">
    <!-- List Subscribers -->
    <div class="card">
        <h3>Danh sách đăng ký (<?= count($subscribers) ?>)</h3>
        <ul class="subscriber-list">
            <?php foreach ($subscribers as $sub): ?>
                <li class="subscriber-item">
                    <div class="sub-info">
                        <span class="email"><?= htmlspecialchars($sub['email']) ?></span>
                        <span class="date"><?= date('d/m/Y', strtotime($sub['subscribed_at'])) ?></span>
                    </div>
                    <div class="sub-actions">
                        <span class="status <?= $sub['status'] ?>"><?= $sub['status'] ?></span>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa subscriber này?')">
                            <?= $auth->getCSRFInput() ?>
                            <input type="hidden" name="action" value="delete_subscriber">
                            <input type="hidden" name="subscriber_id" value="<?= $sub['id'] ?>">
                            <button type="submit" class="btn-delete" title="Xóa">×</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if (empty($subscribers)): ?>
                <li style="padding: 10px; color: #999;">Chưa có người đăng ký.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Compose Newsletter -->
    <div class="card">
        <h3>Gửi Email Newsletter</h3>
        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
            Soạn nội dung và gửi email đến tất cả subscriber đã xác nhận, hoặc gửi thử nghiệm đến email admin.
        </p>
        <form method="POST" id="newsletter-compose-form">
            <?= $auth->getCSRFInput() ?>
            <div class="form-group">
                <label>Tiêu đề</label>
                <input type="text" name="subject" class="form-control" placeholder="Tiêu đề email..." value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Nội dung</label>
                <textarea name="content" class="form-control" rows="10" placeholder="Soạn nội dung email..." required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                <small style="color: #999; font-size: 12px;">Nội dung sẽ được gửi trong template email premium của website.</small>
            </div>
            <div class="btn-group">
                <button type="submit" name="action" value="send_test" class="btn btn-secondary">
                    📧 Gửi Thử Nghiệm
                </button>
                <button type="submit" name="action" value="send_all" class="btn btn-primary" onclick="return confirm('Gửi email đến tất cả subscriber đã xác nhận?')">
                    📨 Gửi Tất Cả (<?= count(array_filter($subscribers, fn($s) => $s['status'] === 'active')) ?> active)
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; }
    .subscriber-list { list-style: none; padding: 0; max-height: 500px; overflow-y: auto; }
    .subscriber-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee; font-size: 14px; }
    .sub-info { display: flex; gap: 12px; align-items: center; }
    .sub-actions { display: flex; gap: 8px; align-items: center; }
    .subscriber-item .email { font-weight: 500; }
    .subscriber-item .status { font-size: 12px; padding: 2px 6px; border-radius: 4px; }
    .status.active { background: #e6fffa; color: #00baa4; }
    .status.unconfirmed { background: #fff5cb; color: #b4850a; }
    .btn-delete { background: none; border: 1px solid #ddd; color: #dc3545; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 16px; line-height: 1; display: flex; align-items: center; justify-content: center; }
    .btn-delete:hover { background: #dc3545; color: white; border-color: #dc3545; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; display: block; font-family: inherit; font-size: 14px; }
    .form-control:focus { outline: none; border-color: var(--color-gold, #ECB613); box-shadow: 0 0 0 2px rgba(236,182,19,0.15); }
    textarea.form-control { resize: vertical; min-height: 150px; }
    .btn-group { display: flex; gap: 12px; margin-top: 10px; }
    .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s; }
    .btn-secondary { background: #f0f0f0; color: #333; }
    .btn-secondary:hover { background: #e0e0e0; }
    .btn-primary { background: var(--color-gold, #ECB613); color: #1C1F1D; }
    .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
    @media (max-width: 768px) {
        .grid-layout { grid-template-columns: 1fr; }
        .btn-group { flex-direction: column; }
    }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
