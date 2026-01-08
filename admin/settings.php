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
            // Create setting if not exists
            $type = ($key === 'hero_slides') ? 'json' : 'string';
            $db->insert('site_settings', [
                'setting_key' => $key, 
                'setting_value' => $value,
                'setting_type' => $type
            ]);
        }
    }
    $success = 'Cập nhật cài đặt thành công!';
    // Refresh settings
    $settings = $db->fetchAll("SELECT * FROM site_settings");
    foreach ($settings as $s) {
        $settingsMap[$s['setting_key']] = $s;
    }
}

// Fetch Settings (if not POST)
if (empty($settingsMap)) {
    $settings = $db->fetchAll("SELECT * FROM site_settings");
    $settingsMap = [];
    foreach ($settings as $s) {
        $settingsMap[$s['setting_key']] = $s;
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header">
    <h1>Cài đặt hệ thống</h1>
</div>

<?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>

<form method="POST" action="">
    <div class="card">
        <h3>Slide ảnh Trang chủ (Hero)</h3>
        <p class="text-muted">Quản lý hình ảnh hiển thị ở slide đầu trang chủ.</p>
        
        <input type="hidden" name="hero_slides" id="hero_slides_input" value='<?= $settingsMap['hero_slides']['setting_value'] ?? '[]' ?>'>
        
        <div id="slides_preview" class="slides-grid">
            <!-- JS will populate this -->
        </div>
        
        <div class="upload-btn-wrapper mt-3">
            <button type="button" class="btn btn-secondary" id="upload_slide_btn">+ Thêm ảnh</button>
            <input type="file" id="slide_file_input" accept="image/*" style="display: none;">
        </div>
    </div>

    <div class="card mt-4">
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
    .mt-3 { margin-top: 16px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 15px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
    .text-muted { color: #888; font-size: 13px; margin-top: 5px; display: block; }
    .btn-lg { padding: 15px 30px; font-size: 16px; }
    
    /* Slideshow Styles */
    .slides-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 16px;
        margin-top: 16px;
    }
    .slide-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        aspect-ratio: 16/9;
        border: 1px solid #eee;
    }
    .slide-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .slide-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(255, 0, 0, 0.8);
        color: white;
        border: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slideInput = document.getElementById('slide_file_input');
    const uploadBtn = document.getElementById('upload_slide_btn');
    const previewGrid = document.getElementById('slides_preview');
    const heroSlidesInput = document.getElementById('hero_slides_input');
    
    // Load initial slides
    let slides = [];
    try {
        slides = JSON.parse(heroSlidesInput.value || '[]');
    } catch(e) { slides = []; }
    
    renderSlides();
    
    uploadBtn.addEventListener('click', () => slideInput.click());
    
    slideInput.addEventListener('change', async function() {
        if (this.files.length === 0) return;
        
        const file = this.files[0];
        const formData = new FormData();
        formData.append('file', file);
        
        uploadBtn.textContent = 'Đang tải lên...';
        uploadBtn.disabled = true;
        
        try {
            const response = await fetch('/api/upload-media.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                // Correctly access data structure from api/upload-media.php
                // API returns { success: true, data: { url: '...', filename: '...' } }
                let imageUrl = '';
                
                if (result.data && result.data.url) {
                    imageUrl = result.data.url;
                } else if (result.url) {
                    imageUrl = result.url;
                } else if (result.filename) {
                    imageUrl = '/assets/uploads/' + result.filename;
                } else if (result.data && result.data.filename) {
                     imageUrl = '/assets/uploads/' + result.data.filename;
                }
                
                if (imageUrl) {
                    slides.push(imageUrl);
                    renderSlides();
                    updateInput();
                } else {
                    alert('Lỗi: Không nhận được đường dẫn ảnh');
                }
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (error) {
            console.error(error);
            alert('Lỗi kết nối');
        } finally {
            uploadBtn.textContent = '+ Thêm ảnh';
            uploadBtn.disabled = false;
            slideInput.value = '';
        }
    });
    
    function renderSlides() {
        previewGrid.innerHTML = '';
        slides.forEach((url, index) => {
            const div = document.createElement('div');
            div.className = 'slide-item';
            
            // Fix display URL if it is relative 'uploads/' and we need full path for Admin preview
            // If url starts with 'http', use it. Else prepend '/uploads/'? 
            // Better: rely on server path.
            // If I store "uploads/example.jpg", checking if it renders.
            
            // For preview, if it's local path without slash, prepend /
            let displayUrl = url;
            if (!url.startsWith('http') && !url.startsWith('/')) {
                displayUrl = '/' + url;
            }
            
            div.innerHTML = `
                <img src="${displayUrl}" onerror="this.src='/assets/images/placeholder.jpg'">
                <button type="button" class="slide-remove" data-index="${index}">×</button>
            `;
            previewGrid.appendChild(div);
        });
        
        // Add delete events
        document.querySelectorAll('.slide-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                slides.splice(idx, 1);
                renderSlides();
                updateInput();
            });
        });
    }
    
    function updateInput() {
        heroSlidesInput.value = JSON.stringify(slides);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
