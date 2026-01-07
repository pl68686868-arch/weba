<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$success = '';
$error = '';

// Handle Image Upload & Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = __DIR__ . '/../assets/uploads/';
    
    // List of allowed image keys
    $imageKeys = [
        'about_hero_image',
        'teaching_hero_image',
        'podcast_cover_art',
        'contact_hero_image'
    ];

    try {
        foreach ($imageKeys as $key) {
            // Check if a file was uploaded for this key
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$key];
                
                // Validate Image
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes)) {
                    throw new Exception("Định dạng file không hợp lệ cho {$key}. Chỉ chấp nhận JPG, PNG, WEBP.");
                }

                // Generate Safe Filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFilename = "{$key}_" . time() . ".{$extension}";
                $destination = $uploadDir . $newFilename;

                // Move File
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Save relative path to DB
                    set_setting($key, $newFilename);
                } else {
                    throw new Exception("Không thể lưu file {$key}.");
                }
            }
        }
        $success = 'Đã cập nhật hình ảnh thành công!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch Current Settings
$images = [];
$imageKeys = ['about_hero_image', 'teaching_hero_image', 'podcast_cover_art', 'contact_hero_image'];
foreach ($imageKeys as $key) {
    $images[$key] = get_setting($key, '');
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header">
    <h1>Quản lý Giao diện & Hình ảnh</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="appearance-form">
    
    <!-- About Page -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Trang Giới thiệu (About)</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Ảnh Chân dung (Hero Section)</label>
                <div class="image-preview-wrapper">
                    <?php if ($images['about_hero_image']): ?>
                        <div class="current-image">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['about_hero_image']) ?>" alt="Current About Image">
                        </div>
                    <?php else: ?>
                        <div class="placeholder-box">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="about_hero_image" class="form-control-file" accept="image/*">
                    <small class="text-muted">Kích thước khuyên dùng: 600x750px (Tỉ lệ 4:5)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Teaching Page -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Trang Giảng dạy (Teaching)</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Ảnh Minh họa (Hero Section)</label>
                <div class="image-preview-wrapper">
                    <?php if ($images['teaching_hero_image']): ?>
                        <div class="current-image">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['teaching_hero_image']) ?>" alt="Current Teaching Image">
                        </div>
                    <?php else: ?>
                        <div class="placeholder-box">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="teaching_hero_image" class="form-control-file" accept="image/*">
                    <small class="text-muted">Kích thước khuyên dùng: 600x750px (Tỉ lệ 4:5)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Podcast Page -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Trang Podcast</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Ảnh Bìa Podcast (Cover Art)</label>
                <div class="image-preview-wrapper">
                    <?php if ($images['podcast_cover_art']): ?>
                        <div class="current-image">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['podcast_cover_art']) ?>" alt="Current Podcast Cover">
                        </div>
                    <?php else: ?>
                        <div class="placeholder-box">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="podcast_cover_art" class="form-control-file" accept="image/*">
                    <small class="text-muted">Kích thước khuyên dùng: 800x800px (Vuông)</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Page -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Trang Liên hệ</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Ảnh Minh họa (Hero/Layout)</label>
                <div class="image-preview-wrapper">
                    <?php if ($images['contact_hero_image']): ?>
                        <div class="current-image">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['contact_hero_image']) ?>" alt="Current Contact Image">
                        </div>
                    <?php else: ?>
                        <div class="placeholder-box">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="contact_hero_image" class="form-control-file" accept="image/*">
                    <small class="text-muted">Kích thước khuyên dùng: 600x800px</small>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions sticky-actions">
        <button type="submit" class="btn btn-primary btn-lg">Lưu thay đổi</button>
    </div>
</form>

<style>
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .card-header {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
    }
    .card-header h3 { margin: 0; font-size: 1.1rem; color: #333; }
    .card-body { padding: 20px; }
    
    .image-preview-wrapper {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 10px;
    }
    
    .current-image img {
        max-width: 200px;
        max-height: 250px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        object-fit: cover;
    }
    
    .placeholder-box {
        width: 150px;
        height: 150px;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        border-radius: 4px;
        border: 2px dashed #ddd;
    }
    
    .mb-4 { margin-bottom: 1.5rem; }
    
    .sticky-actions {
        position: sticky;
        bottom: 20px;
        background: white;
        padding: 15px 20px;
        border-top: 1px solid #eee;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        border-radius: 8px;
        display: flex;
        justify-content: flex-end;
        z-index: 100;
    }
    
    .btn-lg {
        padding: 12px 30px;
        font-size: 1rem;
    }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
