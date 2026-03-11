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
    <div class="admin-page__header">
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
        <div class="appearance-grid">
            
            <!-- About Page -->
            <div class="card">
                <div class="appearance-card-header">
                    <div class="appearance-icon"><i class="ph ph-user-circle"></i></div>
                    <h3>Trang Giới thiệu</h3>
                </div>
                
                <div class="appearance-card-body">
                    <div class="image-thumb">
                        <?php if ($images['about_hero_image']): ?>
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['about_hero_image']) ?>" alt="About Hero">
                        <?php else: ?>
                            <div class="image-thumb-empty"><i class="ph ph-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="appearance-form">
                        <label class="form-label">Ảnh Chân dung (Hero)</label>
                        <input type="file" name="about_hero_image" class="form-control" accept="image/*">
                        <p class="form-hint">Không vượt 2MB • 600×750px (4:5)</p>
                    </div>
                </div>
            </div>

            <!-- Teaching Page -->
            <div class="card">
                <div class="appearance-card-header">
                    <div class="appearance-icon"><i class="ph ph-graduation-cap"></i></div>
                    <h3>Trang Giảng dạy</h3>
                </div>
                
                <div class="appearance-card-body">
                    <div class="image-thumb">
                        <?php if ($images['teaching_hero_image']): ?>
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['teaching_hero_image']) ?>" alt="Teaching Hero">
                        <?php else: ?>
                            <div class="image-thumb-empty"><i class="ph ph-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="appearance-form">
                        <label class="form-label">Ảnh Minh họa (Hero)</label>
                        <input type="file" name="teaching_hero_image" class="form-control" accept="image/*">
                        <p class="form-hint">Không vượt 2MB • 600×750px (4:5)</p>
                    </div>
                </div>
            </div>

            <!-- Podcast Page -->
            <div class="card">
                <div class="appearance-card-header">
                    <div class="appearance-icon"><i class="ph ph-microphone-stage"></i></div>
                    <h3>Trang Podcast</h3>
                </div>
                
                <div class="appearance-card-body">
                    <div class="image-thumb square">
                        <?php if ($images['podcast_cover_art']): ?>
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['podcast_cover_art']) ?>" alt="Podcast Cover">
                        <?php else: ?>
                            <div class="image-thumb-empty"><i class="ph ph-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="appearance-form">
                        <label class="form-label">Ảnh Bìa Podcast (Cover Art)</label>
                        <input type="file" name="podcast_cover_art" class="form-control" accept="image/*">
                        <p class="form-hint">Không vượt 2MB • 800×800px (1:1)</p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Page -->
            <div class="card">
                <div class="appearance-card-header">
                    <div class="appearance-icon"><i class="ph ph-phone"></i></div>
                    <h3>Trang Liên hệ</h3>
                </div>
                
                <div class="appearance-card-body">
                    <div class="image-thumb">
                        <?php if ($images['contact_hero_image']): ?>
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($images['contact_hero_image']) ?>" alt="Contact Hero">
                        <?php else: ?>
                            <div class="image-thumb-empty"><i class="ph ph-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="appearance-form">
                        <label class="form-label">Ảnh Minh họa</label>
                        <input type="file" name="contact_hero_image" class="form-control" accept="image/*">
                        <p class="form-hint">Không vượt 2MB • 600×800px</p>
                    </div>
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

<style>
    .appearance-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    .appearance-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    .appearance-card-header h3 { margin: 0; }
    .appearance-icon {
        width: 40px; height: 40px;
        background: rgba(44, 95, 79, 0.1);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        color: var(--color-primary);
    }
    .appearance-card-body {
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }
    .image-thumb {
        width: 120px;
        min-width: 120px;
        height: 150px;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid var(--border-color);
        background: var(--bg-body);
    }
    .image-thumb.square {
        height: 120px;
    }
    .image-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .image-thumb-empty {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        color: var(--text-muted);
        font-size: 2rem;
        opacity: 0.3;
    }
    .appearance-form {
        flex: 1;
    }
    .form-hint {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 8px;
    }
    @media (max-width: 900px) {
        .appearance-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 500px) {
        .appearance-card-body { flex-direction: column; }
        .image-thumb { width: 100%; height: 160px; min-width: auto; }
    }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
