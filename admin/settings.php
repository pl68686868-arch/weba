<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

// ============================================
// AJAX UPLOAD HANDLER - Xử lý upload ảnh slide
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'upload_slide') {
    header('Content-Type: application/json');
    
    if (!isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'No file']);
        exit;
    }
    
    $file = $_FILES['file'];
    $uploadDir = __DIR__ . '/../assets/uploads/';
    
    // Tạo thư mục nếu chưa có
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Kiểm tra file type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }
    
    // Tạo tên file mới
    $filename = uniqid('slide_') . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Trả về URL đầy đủ
        $url = '/assets/uploads/' . $filename;
        echo json_encode([
            'success' => true,
            'url' => $url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
    exit;
}

// ============================================
// NORMAL PAGE - Xử lý form và hiển thị
// ============================================
$success = '';
$error = '';

// Handle Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;
        
        $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = :key", ['key' => $key]);
        
        if ($exists) {
            $db->update('site_settings', ['setting_value' => $value], 'setting_key = :key', ['key' => $key]);
        } else {
            $type = ($key === 'hero_slides') ? 'json' : 'string';
            $db->insert('site_settings', [
                'setting_key' => $key, 
                'setting_value' => $value,
                'setting_type' => $type
            ]);
        }
    }
    
    // Clear hero_slides cache from DATABASE
    try {
        $db->query("DELETE FROM cache WHERE cache_key = 'hero_slides'");
    } catch (Exception $e) {
        // Ignore if cache table doesn't exist
    }
    
    $success = 'Cập nhật cài đặt thành công!';
}

// Fetch all settings
$settings = $db->fetchAll("SELECT * FROM site_settings");
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['setting_key']] = $s;
}

require_once __DIR__ . '/../includes/admin-header.php';
?>
    <div class="admin-header">
        <div>
            <h1>Cài đặt hệ thống</h1>
            <p>Cấu hình thông tin cơ bản, nội dung trang chủ và các thông số khác.</p>
        </div>
    </div>

    <?php if ($success): ?> 
        <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(16, 185, 129, 0.2); font-weight: 500;">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="dashboard-grid" style="grid-template-columns: 1fr; gap: 32px;">
            <!-- Hero Slides -->
            <div class="card">
                <h3 style="margin-bottom: 12px;">🖼️ Slide ảnh Trang chủ (Hero)</h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 24px;">Quản lý tập hợp các hình ảnh hiển thị ở phần đầu trang chủ.</p>
                
                <input type="hidden" name="hero_slides" id="hero_slides_input" value='<?= htmlspecialchars($settingsMap['hero_slides']['setting_value'] ?? '[]') ?>'>
                
                <div id="slides_preview" class="dashboard-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                    <!-- JS will populate this -->
                </div>
                
                <div style="margin-top: 24px; display: flex; justify-content: center;">
                    <button type="button" class="btn btn-secondary" id="upload_slide_btn" style="width: 100%; max-width: 400px; border-style: dashed; border-width: 2px;">
                        ✨ Thêm ảnh slide mới
                    </button>
                    <input type="file" id="slide_file_input" accept="image/*" style="display: none;">
                </div>
            </div>

            <!-- General Settings -->
            <div class="card">
                <h3 style="margin-bottom: 24px;">⚙️ Thông tin chung</h3>
                
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div class="form-group">
                        <label class="form-label">Tên Website</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settingsMap['site_name']['setting_value'] ?? '') ?>" placeholder="Ví dụ: Dương Trần Minh Đoàn">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Khẩu hiệu (Tagline)</label>
                        <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($settingsMap['site_tagline']['setting_value'] ?? '') ?>" placeholder="Ví dụ: Sống tỉnh thức & Hạnh phúc">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả SEO (Meta Description)</label>
                    <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['site_description']['setting_value'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Home Page Hero -->
            <div class="card">
                <h3 style="margin-bottom: 24px;">🏠 Trang Chủ — Hero Section</h3>
                
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div class="form-group">
                        <label class="form-label">Tên hiển thị (Hero)</label>
                        <input type="text" name="hero_name" class="form-control" value="<?= htmlspecialchars($settingsMap['hero_name']['setting_value'] ?? 'Dương Trần Minh Đoàn') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phụ đề (Subtitle)</label>
                        <input type="text" name="hero_subtitle" class="form-control" value="<?= htmlspecialchars($settingsMap['hero_subtitle']['setting_value'] ?? 'Giảng viên, người thực hành tâm lý và chánh niệm') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Đoạn giới thiệu bản thân (Hero Bio)</label>
                    <textarea name="hero_bio" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['hero_bio']['setting_value'] ?? 'Tôi là giảng viên và người thực hành tâm lý, thực hành và giảng dạy dựa trên nền tảng chánh niệm. Công việc của tôi gắn liền với việc quan sát, lắng nghe và đồng hành cùng đời sống nội tâm.') ?></textarea>
                </div>
            </div>

            <!-- Home Page Intro & Testimonials -->
            <div class="dashboard-grid" style="grid-template-columns: 1.5fr 1fr; gap: 32px;">
                <div class="card">
                    <h3 style="margin-bottom: 24px;">🏠 Trang Chủ — Giới thiệu</h3>
                    <div class="form-group">
                        <label class="form-label">Đoạn giới thiệu 1</label>
                        <textarea name="intro_paragraph_1" class="form-control" rows="4"><?= htmlspecialchars($settingsMap['intro_paragraph_1']['setting_value'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Đoạn giới thiệu 2</label>
                        <textarea name="intro_paragraph_2" class="form-control" rows="4"><?= htmlspecialchars($settingsMap['intro_paragraph_2']['setting_value'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 24px;">🏠 Trang Chủ — Nhận xét</h3>
                    <div class="form-group">
                        <label class="form-label">Lời nhận xét 1</label>
                        <textarea name="testimonial_1_quote" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['testimonial_1_quote']['setting_value'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lời nhận xét 2</label>
                        <textarea name="testimonial_2_quote" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['testimonial_2_quote']['setting_value'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Configuration -->
            <div class="card">
                <h3 style="margin-bottom: 24px;">🛠️ Cấu hình kỹ thuật</h3>
                <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 24px;">
                    <div class="form-group">
                        <label class="form-label">Số bài/trang</label>
                        <input type="number" name="posts_per_page" class="form-control" value="<?= htmlspecialchars($settingsMap['posts_per_page']['setting_value'] ?? '10') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Google Analytics ID</label>
                        <input type="text" name="google_analytics_id" class="form-control" placeholder="UA-XXXXX-Y" value="<?= htmlspecialchars($settingsMap['google_analytics_id']['setting_value'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bản quyền Footer</label>
                        <input type="text" name="footer_copyright" class="form-control" value="<?= htmlspecialchars($settingsMap['footer_copyright']['setting_value'] ?? '© 2026 Dương Trần Minh Đoàn.') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div style="position: sticky; bottom: 32px; right: 32px; display: flex; justify-content: flex-end; z-index: 100;">
            <button type="submit" class="btn btn-primary" style="padding: 16px 48px; font-size: 1rem; box-shadow: var(--shadow-xl);">
                💾 Lưu tất cả cài đặt
            </button>
        </div>
    </form>
</div>
<style>
    .slide-item {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        aspect-ratio: 16/9;
        border: 2px solid var(--border-color);
        background: var(--bg-card);
    }
    .slide-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .slide-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .slide-remove:hover {
        background: #DC2626;
        transform: scale(1.1);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slideInput = document.getElementById('slide_file_input');
    const uploadBtn = document.getElementById('upload_slide_btn');
    const previewGrid = document.getElementById('slides_preview');
    const heroSlidesInput = document.getElementById('hero_slides_input');
    
    // Load existing slides
    let slides = [];
    try {
        slides = JSON.parse(heroSlidesInput.value || '[]');
    } catch(e) { 
        slides = []; 
    }
    
    renderSlides();
    
    // Click button to trigger file input
    uploadBtn.addEventListener('click', () => slideInput.click());
    
    // Handle file selection
    slideInput.addEventListener('change', async function() {
        if (this.files.length === 0) return;
        
        const file = this.files[0];
        const formData = new FormData();
        formData.append('file', file);
        
        uploadBtn.textContent = 'Đang tải lên...';
        uploadBtn.disabled = true;
        
        try {
            // Upload to SAME FILE with action parameter
            const response = await fetch('settings.php?action=upload_slide', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            console.log('Response:', text); // Debug
            
            let result;
            try {
                result = JSON.parse(text);
            } catch(e) {
                throw new Error('Invalid JSON: ' + text.substring(0, 100));
            }
            
            if (result.success) {
                slides.push(result.url);
                renderSlides();
                updateInput();
            } else {
                alert('Lỗi: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert('Lỗi: ' + error.message);
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
            div.innerHTML = `
                <img src="${url}" onerror="this.src='/assets/images/placeholder.jpg'">
                <button type="button" class="slide-remove" data-index="${index}">×</button>
            `;
            previewGrid.appendChild(div);
        });
        
        // Add delete handlers
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
