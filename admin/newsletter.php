<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

$subscribers = $db->fetchAll("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header">
    <h1>Newsletter</h1>
</div>

<div class="grid-layout">
    <!-- List Subscribers -->
    <div class="card">
        <h3>Danh sách đăng ký (<?= count($subscribers) ?>)</h3>
        <ul class="subscriber-list">
            <?php foreach ($subscribers as $sub): ?>
                <li class="subscriber-item">
                    <span class="email"><?= htmlspecialchars($sub['email']) ?></span>
                    <span class="date"><?= date('d/m/Y', strtotime($sub['subscribed_at'])) ?></span>
                    <span class="status <?= $sub['status'] ?>"><?= $sub['status'] ?></span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($subscribers)): ?>
                <li style="padding: 10px; color: #999;">Chưa có người đăng ký.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Compose (Mockup) -->
    <div class="card">
        <h3>Gửi Email Mới</h3>
        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
            Hệ thống gửi email đang được tích hợp. Hiện tại bạn có thể soạn thảo mẫu.
        </p>
        <form>
            <div class="form-group">
                <label>Tiêu đề</label>
                <input type="text" class="form-control" placeholder="Tiêu đề email...">
            </div>
            <div class="form-group">
                <label>Nội dung</label>
                <textarea class="form-control" rows="10" placeholder="Soạn nội dung..."></textarea>
            </div>
            <button type="button" class="btn" onclick="alert('Tính năng gửi email đang được phát triển!')">Gửi Thử Nghiệm</button>
        </form>
    </div>
</div>

<style>
    .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; }
    .subscriber-list { list-style: none; padding: 0; max-height: 500px; overflow-y: auto; }
    .subscriber-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; font-size: 14px; }
    .subscriber-item .email { font-weight: 500; }
    .subscriber-item .status { font-size: 12px; padding: 2px 6px; border-radius: 4px; }
    .status.active { background: #e6fffa; color: #00baa4; }
    .status.unconfirmed { background: #fff5cb; color: #b4850a; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; display: block; }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
