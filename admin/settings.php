<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

$success = '';
$error = '';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;
        
        // Check if setting exists
        $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = :key", ['key' => $key]);
        
        if ($exists) {
            $db->update('site_settings', ['setting_value' => $value], 'setting_key = :key', ['key' => $key]);
        } else {
            // Optional: Create if not exists (security risk? maybe limit keys)
        }
    }
    $success = 'Cập nhật cài đặt thành công!';
}

// Fetch Settings
$settings = $db->fetchAll("SELECT * FROM site_settings");
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['setting_key']] = $s;
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header">
    <h1>Cài đặt hệ thống</h1>
</div>

<?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>

<form method="POST" action="">
    <div class="card">
        <h3>Thông tin chung</h3>
        
        <div class="form-group">
            <label>Tên Website</label>
            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settingsMap['site_name']['setting_value'] ?? '') ?>">
            <small class="text-muted">Tiêu đề chính hiển thị trên tab trình duyệt.</small>
        </div>

        <div class="form-group">
            <label>Khẩu hiệu (Tagline)</label>
            <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($settingsMap['site_tagline']['setting_value'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Mô tả (Meta Description)</label>
            <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['site_description']['setting_value'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="card mt-4">
        <h3>Cấu hình khác</h3>
        
        <div class="form-group">
            <label>Số bài viết mỗi trang</label>
            <input type="number" name="posts_per_page" class="form-control" style="width: 100px;" value="<?= htmlspecialchars($settingsMap['posts_per_page']['setting_value'] ?? '10') ?>">
        </div>

        <div class="form-group">
            <label>Google Analytics ID</label>
            <input type="text" name="google_analytics_id" class="form-control" placeholder="UA-XXXXX-Y" value="<?= htmlspecialchars($settingsMap['google_analytics_id']['setting_value'] ?? '') ?>">
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-lg">Lưu cài đặt</button>
    </div>
</form>

<style>
    .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .mt-4 { margin-top: 24px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 15px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
    .text-muted { color: #888; font-size: 13px; margin-top: 5px; display: block; }
    .btn-lg { padding: 15px 30px; font-size: 16px; }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
