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
                    
                    $sendResult = "Đã gửi thành công " . (string)$sent . "/" . (string)count($activeSubscribers) . " email." . ($failed > 0 ? " (" . (string)$failed . " thất bại)" : '');
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

<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1>Bản tin Newsletter</h1>
            <p>Quản lý danh sách đăng ký và gửi thông báo email.</p>
        </div>
        <div class="admin-page__actions">
            <div class="header-stat">
                <span class="stat-icon">📧</span>
                <div class="stat-details">
                    <span class="stat-label">Subscribers</span>
                    <span class="stat-value"><?= (string)count($subscribers) ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($sendResult): ?>
        <div class="alert alert-success">
            <span class="icon">✅</span> <?= htmlspecialchars($sendResult) ?>
        </div>
    <?php endif; ?>

    <?php if ($sendError): ?>
        <div class="alert alert-error">
            <span class="icon">⚠️</span> <?= htmlspecialchars($sendError) ?>
        </div>
    <?php endif; ?>

    <div class="newsletter-grid">
        <!-- List Subscribers -->
        <div class="card p-0 overflow-hidden">
            <div class="card-header p-6 pb-0">
                <h3 class="card-title">👥 Danh sách đăng ký</h3>
            </div>
            <div class="table-container max-h-[600px] overflow-y-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="pl-6">Email</th>
                            <th>Ngày</th>
                            <th class="text-right pr-6">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $sub): ?>
                            <tr>
                                <td class="pl-6">
                                    <div class="subscriber-email"><?= htmlspecialchars($sub['email']) ?></div>
                                    <span class="status-badge status-<?= $sub['status'] ?>">
                                        <?= $sub['status'] === 'active' ? 'Đã xác nhận' : 'Chưa xác nhận' ?>
                                    </span>
                                </td>
                                <td class="subscriber-date">
                                    <?= date('d/m/Y', strtotime($sub['subscribed_at'])) ?>
                                </td>
                                <td class="text-right pr-6">
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Xóa subscriber này?')">
                                        <?= $auth->getCSRFInput() ?>
                                        <input type="hidden" name="action" value="delete_subscriber">
                                        <input type="hidden" name="subscriber_id" value="<?= $sub['id'] ?>">
                                        <button type="submit" class="btn-icon btn-icon-danger">
                                            ✕
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="3" class="empty-cell">
                                    Chưa có người đăng ký.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Compose Newsletter -->
        <div class="card">
            <h3 class="card-title">✉️ Soạn tin thông báo</h3>
            <p class="card-subtitle">
                Tin nhắn sẽ được tự động chèn vào template email cao cấp của thương hiệu.
            </p>
            <form method="POST" id="newsletter-compose-form">
                <?= $auth->getCSRFInput() ?>
                <div class="form-group">
                    <label class="form-label">Tiêu đề email</label>
                    <input type="text" name="subject" class="form-control" placeholder="Ví dụ: Cập nhật mới từ Đặng Tuấn Anh..." value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nội dung thông điệp</label>
                    <textarea name="content" class="form-control" rows="12" placeholder="Nhập nội dung tin nhắn của bạn tại đây..." required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    <small class="form-hint">Hệ thống hỗ trợ tự động căn lề và định dạng theo chuẩn premium.</small>
                </div>
                <div class="newsletter-actions">
                    <button type="submit" name="action" value="send_test" class="btn btn-secondary flex-1">
                        🧪 Gửi thử (Admin)
                    </button>
                    <button type="submit" name="action" value="send_all" class="btn btn-primary flex-2" onclick="return confirm('Gửi email đến tất cả subscriber đã xác nhận?')">
                        🚀 Gửi cho toàn bộ (<?= (string)count(array_filter($subscribers, fn($s) => $s['status'] === 'active')) ?> active)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .newsletter-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 32px; }
    .p-0 { padding: 0; }
    .p-6 { padding: 1.5rem; }
    .pb-0 { padding-bottom: 0; }
    .pl-6 { padding-left: 1.5rem; }
    .pr-6 { padding-right: 1.5rem; }
    .text-right { text-align: right; }
    .inline-block { display: inline-block; }
    .flex-1 { flex: 1; }
    .flex-2 { flex: 2; }
    .overflow-hidden { overflow: hidden; }
    
    .header-stat { background: var(--bg-card); padding: 12px 24px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm); }
    .stat-icon { font-size: 1.5rem; }
    .stat-details { display: flex; flex-direction: column; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em; }
    .stat-value { font-size: 1.125rem; font-weight: 700; color: var(--color-primary); }
    
    .card-subtitle { color: var(--text-muted); font-size: 0.875rem; margin-bottom: 24px; margin-top: -12px; }
    
    .subscriber-email { font-weight: 600; color: var(--text-main); margin-bottom: 4px; }
    .subscriber-date { font-size: 0.8125rem; color: var(--text-muted); }
    .empty-cell { text-align: center; padding: 64px !important; color: var(--text-muted); }
    
    .form-hint { color: var(--text-muted); margin-top: 8px; display: block; font-size: 0.75rem; }
    
    .newsletter-actions { display: flex; gap: 12px; margin-top: 32px; }

    @media (max-width: 1024px) {
        .newsletter-grid { grid-template-columns: 1fr; }
    }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
