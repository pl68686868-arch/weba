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
    <h1>Cài đặt hệ thống</h1>
</div>

<?php if ($success): ?> 
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div> 
<?php endif; ?>

<form method="POST" action="">
    <div class="card">
        <h3>Slide ảnh Trang chủ (Hero)</h3>
        <p class="text-muted">Quản lý hình ảnh hiển thị ở slide đầu trang chủ.</p>
        
        <input type="hidden" name="hero_slides" id="hero_slides_input" value='<?= htmlspecialchars($settingsMap['hero_slides']['setting_value'] ?? '[]') ?>'>
        
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

    <!-- ========================================== -->
    <!-- NỘI DUNG TRANG CHỦ (index.php)             -->
    <!-- ========================================== -->
    <div class="card mt-4">
        <h3>🏠 Trang Chủ — Hero Section</h3>
        <p class="text-muted">Nội dung hiển thị ở phần đầu trang chủ (Hero).</p>

        <div class="form-group">
            <label>Tên hiển thị (Hero)</label>
            <input type="text" name="hero_name" class="form-control" value="<?= htmlspecialchars($settingsMap['hero_name']['setting_value'] ?? 'Dương Trần Minh Đoàn') ?>">
        </div>
        <div class="form-group">
            <label>Phụ đề (Subtitle)</label>
            <input type="text" name="hero_subtitle" class="form-control" value="<?= htmlspecialchars($settingsMap['hero_subtitle']['setting_value'] ?? 'Giảng viên, người thực hành tâm lý và chánh niệm') ?>">
        </div>
        <div class="form-group">
            <label>Đoạn giới thiệu bản thân (Hero Bio)</label>
            <textarea name="hero_bio" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['hero_bio']['setting_value'] ?? 'Tôi là giảng viên và người thực hành tâm lý, thực hành và giảng dạy dựa trên nền tảng chánh niệm. Công việc của tôi gắn liền với việc quan sát, lắng nghe và đồng hành cùng đời sống nội tâm.') ?></textarea>
        </div>
    </div>

    <div class="card mt-4">
        <h3>🏠 Trang Chủ — Giới thiệu (Intro)</h3>
        <div class="form-group">
            <label>Đoạn giới thiệu 1</label>
            <textarea name="intro_paragraph_1" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['intro_paragraph_1']['setting_value'] ?? 'Website này là không gian tôi viết và chia sẻ những suy tư, kiến thức và trải nghiệm thực hành dành cho người trưởng thành đang ở trong hành trình tìm kiếm chiều sâu nội tâm, ý nghĩa trong công việc và sự hồi phục thân–tâm.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Đoạn giới thiệu 2</label>
            <textarea name="intro_paragraph_2" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['intro_paragraph_2']['setting_value'] ?? 'Các nội dung được tiếp cận từ góc nhìn tâm lý học ứng dụng, chánh niệm và phản tư nghề nghiệp, với mong muốn góp phần nuôi dưỡng một đời sống tỉnh thức, bền vững và có ý nghĩa hơn.') ?></textarea>
        </div>
    </div>

    <div class="card mt-4">
        <h3>🏠 Trang Chủ — Testimonials</h3>
        <div class="form-group">
            <label>Lời nhận xét 1</label>
            <textarea name="testimonial_1_quote" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['testimonial_1_quote']['setting_value'] ?? 'Thầy Đoàn đã giúp tôi nhìn nhận lại bản thân một cách nhẹ nhàng và sâu sắc. Những buổi hướng dẫn chánh niệm thực sự mang lại sự bình an mà tôi đã tìm kiếm rất lâu.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Tác giả nhận xét 1</label>
            <input type="text" name="testimonial_1_author" class="form-control" value="<?= htmlspecialchars($settingsMap['testimonial_1_author']['setting_value'] ?? '— Học viên khóa Mindfulness At Work') ?>">
        </div>
        <div class="form-group">
            <label>Lời nhận xét 2</label>
            <textarea name="testimonial_2_quote" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['testimonial_2_quote']['setting_value'] ?? 'Cách tiếp cận của thầy rất khoa học nhưng đồng thời cũng rất gần gũi. Tôi học được cách lắng nghe bản thân và không còn sợ đối diện với những cảm xúc khó khăn.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Tác giả nhận xét 2</label>
            <input type="text" name="testimonial_2_author" class="form-control" value="<?= htmlspecialchars($settingsMap['testimonial_2_author']['setting_value'] ?? '— Học viên khóa Tâm lý học Ứng dụng') ?>">
        </div>
    </div>

    <!-- ========================================== -->
    <!-- NỘI DUNG TRANG GIỚI THIỆU (about.php)      -->
    <!-- ========================================== -->
    <div class="card mt-4">
        <h3>📋 Giới Thiệu — Hero</h3>
        <div class="form-group">
            <label>Eyebrow text</label>
            <input type="text" name="about_eyebrow" class="form-control" value="<?= htmlspecialchars($settingsMap['about_eyebrow']['setting_value'] ?? 'Người bạn đồng hành') ?>">
        </div>

        <div class="form-group">
            <label>Mô tả Hero</label>
            <textarea name="about_hero_desc" class="form-control" rows="4"><?= htmlspecialchars($settingsMap['about_hero_desc']['setting_value'] ?? 'Tôi tin rằng việc học cách dừng lại, quan sát và hiểu mình một cách tỉnh thức là nền tảng quan trọng để mỗi người sống và làm việc có ý nghĩa hơn, không chỉ cho hiện tại mà cả về lâu dài.

Tôi là Danny, một người thực hành và giảng dạy tâm lý học ứng dụng, dành sự quan tâm đặc biệt cho đời sống nội tâm của người trưởng thành.') ?></textarea>
        </div>
    </div>

    <div class="card mt-4">
        <h3>📋 Giới Thiệu — Triết lý</h3>
        <div class="form-group">
            <label>Trích dẫn triết lý</label>
            <textarea name="about_philosophy_quote" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['about_philosophy_quote']['setting_value'] ?? 'Không giản lược. Không thần thánh hóa. Chỉ đơn giản là hiểu sâu sắc.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Mô tả triết lý</label>
            <textarea name="about_philosophy_desc" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['about_philosophy_desc']['setting_value'] ?? 'Trong công việc giảng dạy và thực hành, tôi quan tâm đến cách con người trải nghiệm áp lực, ý nghĩa công việc, sự mệt mỏi tinh thần cũng như nhu cầu hồi phục thân–tâm trong đời sống hiện đại. Tôi tiếp cận các vấn đề này từ sự kết hợp giữa tâm lý học, chánh niệm và phản tư nghề nghiệp.') ?></textarea>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- NỘI DUNG TRANG GIẢNG DẠY (teaching.php)     -->
    <!-- ========================================== -->
    <div class="card mt-4">
        <h3>🎓 Giảng Dạy — Hero</h3>

        <div class="form-group">
            <label>Mô tả Hero</label>
            <textarea name="teaching_hero_desc" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['teaching_hero_desc']['setting_value'] ?? 'Tôi tin rằng giáo dục không chỉ là truyền tải kiến thức, mà là quá trình khơi gợi sự chuyển hóa từ bên trong. Hành trình học tập của người trưởng thành cần sự kết hợp giữa hiểu biết khoa học và trải nghiệm thực chứng.') ?></textarea>
        </div>
    </div>

    <div class="card mt-4">
        <h3>🎓 Giảng Dạy — CTA</h3>
        <div class="form-group">
            <label>Tiêu đề CTA</label>
            <input type="text" name="teaching_cta_title" class="form-control" value="<?= htmlspecialchars($settingsMap['teaching_cta_title']['setting_value'] ?? 'Hợp tác Đào tạo') ?>">
        </div>
        <div class="form-group">
            <label>Mô tả CTA</label>
            <textarea name="teaching_cta_desc" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['teaching_cta_desc']['setting_value'] ?? 'Tôi luôn sẵn sàng cho các cơ hội hợp tác giảng dạy tại trường Đại học, Doanh nghiệp hoặc các dự án cộng đồng.') ?></textarea>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- NỘI DUNG TRANG PODCAST (podcast.php)         -->
    <!-- ========================================== -->
    <div class="card mt-4">
        <h3>🎙️ Podcast — Hero</h3>

        <div class="form-group">
            <label>Mô tả Hero</label>
            <textarea name="podcast_hero_desc" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['podcast_hero_desc']['setting_value'] ?? 'Không gian cho những cuộc trò chuyện chậm rãi về những điều thường bị bỏ quên trong sự hối hả của đời sống thường nhật. Nơi chúng ta cùng ngồi lại, lắng nghe và hiểu sâu hơn về chính mình.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Mô tả Đăng ký</label>
            <textarea name="podcast_subscribe_desc" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['podcast_subscribe_desc']['setting_value'] ?? 'Podcast có mặt trên tất cả các nền tảng phổ biến. Đăng ký ngay để không bỏ lỡ tập mới nhất.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Spotify URL</label>
            <input type="url" name="podcast_spotify_url" class="form-control" placeholder="https://open.spotify.com/..." value="<?= htmlspecialchars($settingsMap['podcast_spotify_url']['setting_value'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Apple Podcast URL</label>
            <input type="url" name="podcast_apple_url" class="form-control" placeholder="https://podcasts.apple.com/..." value="<?= htmlspecialchars($settingsMap['podcast_apple_url']['setting_value'] ?? '') ?>">
        </div>
    </div>

    <!-- ========================================== -->
    <!-- NỘI DUNG TRANG LIÊN HỆ (contact.php)        -->
    <!-- ========================================== -->
    <div class="card mt-4">
        <h3>📞 Liên Hệ</h3>

        <div class="form-group">
            <label>Nội dung giới thiệu</label>
            <textarea name="contact_intro" class="form-control" rows="3"><?= htmlspecialchars($settingsMap['contact_intro']['setting_value'] ?? 'Cảm ơn bạn đã ghé thăm. Tôi luôn trân trọng những cơ hội được lắng nghe và chia sẻ về hành trình thực hành tâm lý, giáo dục và chánh niệm.

Nếu bạn có lời mời hợp tác, thắc mắc chuyên môn, hoặc đơn giản là muốn gửi một lời chào, đừng ngần ngại để lại tin nhắn.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Thời gian phản hồi</label>
            <input type="text" name="contact_response_time" class="form-control" value="<?= htmlspecialchars($settingsMap['contact_response_time']['setting_value'] ?? 'Tôi thường kiểm tra email vào buổi sáng và sẽ phản hồi trong vòng 2-3 ngày làm việc.') ?>">
        </div>
    </div>

    <!-- ========================================== -->
    <!-- NỘI DUNG FOOTER                              -->
    <!-- ========================================== -->
    <div class="card mt-4">
        <h3>📎 Footer</h3>
        <div class="form-group">
            <label>Mô tả Footer</label>
            <textarea name="footer_description" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['footer_description']['setting_value'] ?? 'Website này là không gian chia sẻ những suy tư, kiến thức và trải nghiệm thực hành dựa trên nền tảng tâm lý học và chánh niệm.') ?></textarea>
        </div>
        <div class="form-group">
            <label>Bản quyền (Copyright)</label>
            <input type="text" name="footer_copyright" class="form-control" value="<?= htmlspecialchars($settingsMap['footer_copyright']['setting_value'] ?? '© 2026 Dương Trần Minh Đoàn. Mọi quyền được bảo lưu.') ?>">
        </div>
        <div class="form-group">
            <label>Mô tả Newsletter</label>
            <input type="text" name="footer_newsletter_desc" class="form-control" value="<?= htmlspecialchars($settingsMap['footer_newsletter_desc']['setting_value'] ?? 'Nhận bài viết mới nhất qua email. Không spam, chỉ có sự chia sẻ.') ?>">
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
