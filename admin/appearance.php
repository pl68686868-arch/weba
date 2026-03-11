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

<div class="admin-page">
    <div class="admin-header">
        <div>
            <h1>Hình ảnh & Giao diện</h1>
            <p>Tùy chỉnh các hình ảnh đại diện cho từng chuyên trang trên website.</p>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(16, 185, 129, 0.2); font-weight: 500;">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: #DC2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(239, 68, 68, 0.2); font-weight: 500;">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 32px;">
            
            <!-- About Page -->
            <div class="card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                    <div style="width: 40px; height: 40px; background: rgba(44, 95, 79, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">📋</div>
                    <h3 style="margin: 0;">Trang Giới thiệu</h3>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ảnh Chân dung (Hero)</label>
                    <?php if ($images['about_hero_image']): ?>
                        <div class="image-preview" style="margin-bottom: 16px; border-radius: 12px; overflow: hidden; border: 2px solid var(--border-color); aspect-ratio: 4/5;">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['about_hero_image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 16px; border-radius: 12px; aspect-ratio: 4/5; background: var(--bg-body); border: 2px dashed var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.875rem;">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="about_hero_image" class="form-control" accept="image/*">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Kích thước khuyên dùng: 600x750px (4:5)</p>
                </div>
            </div>

            <!-- Teaching Page -->
            <div class="card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                    <div style="width: 40px; height: 40px; background: rgba(44, 95, 79, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">🎓</div>
                    <h3 style="margin: 0;">Trang Giảng dạy</h3>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ảnh Minh họa (Hero)</label>
                    <?php if ($images['teaching_hero_image']): ?>
                        <div class="image-preview" style="margin-bottom: 16px; border-radius: 12px; overflow: hidden; border: 2px solid var(--border-color); aspect-ratio: 4/5;">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['teaching_hero_image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 16px; border-radius: 12px; aspect-ratio: 4/5; background: var(--bg-body); border: 2px dashed var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.875rem;">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="teaching_hero_image" class="form-control" accept="image/*">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Kích thước khuyên dùng: 600x750px (4:5)</p>
                </div>
            </div>

            <!-- Podcast Page -->
            <div class="card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                    <div style="width: 40px; height: 40px; background: rgba(44, 95, 79, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">🎙️</div>
                    <h3 style="margin: 0;">Trang Podcast</h3>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ảnh Bìa Podcast (Cover Art)</label>
                    <?php if ($images['podcast_cover_art']): ?>
                        <div class="image-preview" style="margin-bottom: 16px; border-radius: 12px; overflow: hidden; border: 2px solid var(--border-color); aspect-ratio: 1/1;">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['podcast_cover_art']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 16px; border-radius: 12px; aspect-ratio: 1/1; background: var(--bg-body); border: 2px dashed var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.875rem;">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="podcast_cover_art" class="form-control" accept="image/*">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Kích thước khuyên dùng: 800x800px (1:1)</p>
                </div>
            </div>
            
            <!-- Contact Page -->
            <div class="card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                    <div style="width: 40px; height: 40px; background: rgba(44, 95, 79, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">📞</div>
                    <h3 style="margin: 0;">Trang Liên hệ</h3>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ảnh Minh họa</label>
                    <?php if ($images['contact_hero_image']): ?>
                        <div class="image-preview" style="margin-bottom: 16px; border-radius: 12px; overflow: hidden; border: 2px solid var(--border-color); aspect-ratio: 3/4;">
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['contact_hero_image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 16px; border-radius: 12px; aspect-ratio: 3/4; background: var(--bg-body); border: 2px dashed var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.875rem;">Chưa có ảnh</div>
                    <?php endif; ?>
                    <input type="file" name="contact_hero_image" class="form-control" accept="image/*">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Kích thước khuyên dùng: 600x800px</p>
                </div>
            </div>
        </div>

        <div style="position: sticky; bottom: 32px; right: 32px; display: flex; justify-content: flex-end; z-index: 100;">
            <button type="submit" class="btn btn-primary" style="padding: 16px 48px; font-size: 1rem; box-shadow: var(--shadow-xl);">
                💾 Lưu các thay đổi
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
