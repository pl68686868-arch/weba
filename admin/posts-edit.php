<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$error = '';
$success = '';

// Get Post ID
$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    redirect('/admin/posts.php');
}

// Fetch existing post
$post = $db->fetchOne("SELECT * FROM posts WHERE id = :id", ['id' => $id]);
if (!$post) {
    die("Bài viết không tồn tại.");
}

// Fetch categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");

// Fetch Tags
$currentTags = $db->fetchAll(
    "SELECT t.name FROM tags t 
    JOIN post_tags pt ON t.id = pt.tag_id 
    WHERE pt.post_id = :id", 
    ['id' => $id]
);
$tagList = implode(', ', array_column($currentTags, 'name'));

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $tags_input = $_POST['tags'] ?? '';
    $featured_image = $_POST['featured_image'] ?? '';
    $post_type = $_POST['post_type'] ?? 'post';
    $spotify_url = $_POST['spotify_url'] ?? '';
    
    // SEO Fields
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';

    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề bài viết.';
    } elseif (empty($content)) {
        $error = 'Nội dung bài viết không được để trống.';
    } else {
        // If category is not selected, default to the first available category
        if ($category_id === 0) {
            $defaultCat = $db->fetchOne("SELECT id FROM categories ORDER BY id ASC LIMIT 1");
            if ($defaultCat) {
                $category_id = $defaultCat['id'];
            }
        }
        if (empty($slug)) {
            $slug = createSlug($title);
        } else {
            $slug = createSlug($slug);
        }

        // Check Unique Slug (exclude current post)
        $checkSlug = $db->fetchOne(
            "SELECT id FROM posts WHERE slug = :slug AND id != :id", 
            ['slug' => $slug, 'id' => $id]
        );
        if ($checkSlug) {
            $slug .= '-' . time();
        }

        try {
            $db->beginTransaction();

            $data = [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'excerpt' => $excerpt,
                'category_id' => $category_id,
                'status' => $status,
                'post_type' => $post_type,
                'spotify_url' => $spotify_url,
                'featured_image' => $featured_image,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ];
            
            // Update published_at only if switching to published for first time?
            // For simplicity, update it if status is published
            if ($status === 'published' && $post['status'] !== 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }

            $db->update('posts', $data, 'id = :id', ['id' => $id]);

            // Handle Tags: Remove old links, Add new ones
            // 1. Remove all existing tag links for this post
            $db->delete('post_tags', 'post_id = :id', ['id' => $id]);

            // 2. Add new tags
            if (!empty($tags_input)) {
                $tags = array_map('trim', explode(',', $tags_input));
                foreach ($tags as $tagName) {
                    if (empty($tagName)) continue;
                    
                    $tagSlug = createSlug($tagName);
                    $existingTag = $db->fetchOne("SELECT id FROM tags WHERE slug = :slug", ['slug' => $tagSlug]);
                    
                    if ($existingTag) {
                        $tagId = $existingTag['id'];
                    } else {
                        $tagId = $db->insert('tags', ['name' => $tagName, 'slug' => $tagSlug, 'usage_count' => 1]);
                    }

                    $db->insert('post_tags', ['post_id' => $id, 'tag_id' => $tagId]);
                }
            }

            $db->commit();
            $success = 'Cập nhật bài viết thành công!';
            
            // Refresh data
            $post = $db->fetchOne("SELECT * FROM posts WHERE id = :id", ['id' => $id]);
            $currentTags = $db->fetchAll(
                "SELECT t.name FROM tags t 
                JOIN post_tags pt ON t.id = pt.tag_id 
                WHERE pt.post_id = :id", 
                ['id' => $id]
            );
            $tagList = implode(', ', array_column($currentTags, 'name'));

        } catch (Exception $e) {
            $db->rollback();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <h1>Chỉnh Sửa Bài Viết</h1>
        <div class="header-actions">
            <a href="/<?= $post['slug'] ?>" target="_blank" class="btn btn-text">👁️ Xem bài viết</a>
            <a href="/admin/posts.php" class="btn btn-secondary">← Quay lại danh sách</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="post-form">
        <div class="post-editor-grid">
            <!-- Main Content Column -->
            <div class="post-editor-main">
                <div class="card">
                    <div class="form-group">
                        <label for="title" class="form-label">Tiêu đề bài viết <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-input" required value="<?= htmlspecialchars($post['title']) ?>" placeholder="Nhập tiêu đề hấp dẫn...">
                    </div>

                    <div class="form-group">
                        <label for="slug" class="form-label">Đường dẫn (Slug)</label>
                        <div class="slug-input-wrapper">
                            <input type="text" id="slug" name="slug" class="form-input" value="<?= htmlspecialchars($post['slug']) ?>">
                        </div>
                        <small class="form-hint">Dùng cho đường dẫn URL thân thiện.</small>
                    </div>

                    <div class="form-group">
                        <label for="content" class="form-label">Nội dung <span class="required">*</span></label>
                        <textarea id="content" name="content" rows="20" required class="content-editor"><?= htmlspecialchars($post['content']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="excerpt" class="form-label">Mô tả ngắn (Excerpt)</label>
                        <textarea id="excerpt" name="excerpt" class="form-input" rows="4" placeholder="Viết một đoạn tóm tắt ngắn về bài viết..."><?= htmlspecialchars($post['excerpt']) ?></textarea>
                        <small class="form-hint">Hiển thị ở trang chủ và danh sách bài viết.</small>
                    </div>
                </div>

                <!-- SEO Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Tối ưu hóa tìm kiếm (SEO)</h3>
                    </div>
                    <div class="form-group mt-3">
                        <label for="meta_title" class="form-label">Thẻ tiêu đề (Meta Title)</label>
                        <input type="text" id="meta_title" name="meta_title" class="form-input" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>" placeholder="Tiêu đề hiển thị trên Google...">
                    </div>
                    <div class="form-group">
                        <label for="meta_description" class="form-label">Thẻ mô tả (Meta Description)</label>
                        <textarea id="meta_description" name="meta_description" class="form-input" rows="3" placeholder="Đoạn mô tả ngắn hiển thị trên kết quả tìm kiếm..."><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="post-editor-sidebar">
                <div class="card sticky-sidebar">
                    <div class="card-header">
                        <h3 class="card-title">Cài đặt bài viết</h3>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select id="status" name="status" class="form-select">
                            <option value="draft" <?= ($post['status'] === 'draft') ? 'selected' : '' ?>>Bản nháp</option>
                            <option value="published" <?= ($post['status'] === 'published') ? 'selected' : '' ?>>Công khai</option>
                            <option value="scheduled" <?= ($post['status'] === 'scheduled') ? 'selected' : '' ?>>Lên lịch</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="post_type" class="form-label">Loại nội dung</label>
                        <select id="post_type" name="post_type" class="form-select">
                            <option value="post" <?= ($post['post_type'] === 'post') ? 'selected' : '' ?>>Bài viết (Blog)</option>
                            <option value="podcast" <?= ($post['post_type'] === 'podcast') ? 'selected' : '' ?>>Podcast</option>
                        </select>
                    </div>

                    <div class="form-group" id="spotifyField" style="<?= ($post['post_type'] === 'podcast' ? 'display: block;' : 'display: none;') ?>">
                        <label for="spotify_url" class="form-label">Link Spotify</label>
                        <input type="text" id="spotify_url" name="spotify_url" class="form-input" value="<?= htmlspecialchars($post['spotify_url'] ?? '') ?>" placeholder="https://open.spotify.com/episode/...">
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Chuyên mục</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">-- Chọn chuyên mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?? 'post' ?>" <?= ($post['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tags" class="form-label">Thẻ (Tags)</label>
                        <input type="text" id="tags" name="tags" class="form-input" value="<?= htmlspecialchars($tagList) ?>" placeholder="Cảm xúc, Chữa lành, ...">
                        <small class="form-hint">Phân cách bằng dấu phẩy.</small>
                    </div>

                    <div class="divider"></div>

                    <div class="form-group">
                        <label class="form-label">Ảnh đại diện</label>
                        <div class="featured-image-upload">
                            <input type="text" id="featured_image" name="featured_image" class="form-input mb-2" value="<?= htmlspecialchars($post['featured_image'] ?? '') ?>" placeholder="Nhập URL hoặc upload...">
                            
                            <div class="upload-actions">
                                <button type="button" id="uploadFeaturedBtn" class="btn btn-secondary btn-small">
                                    <span class="icon">📤</span> Tải lên
                                </button>
                                <button type="button" id="openMediaPicker" class="btn btn-text btn-small">
                                    Chọn từ thư viện
                                </button>
                            </div>

                            <input type="file" id="featuredImageFile" accept="image/*" style="display: none;">
                            
                            <div id="uploadProgress" class="upload-progress-container" style="display: none;">
                                <div class="progress-bar-bg">
                                    <div id="progressBar" class="progress-bar-fill"></div>
                                </div>
                                <small id="progressText" class="progress-message"></small>
                            </div>

                            <div id="imagePreview" class="featured-image-preview mt-3" style="<?= ($post['featured_image'] ? 'display: block;' : 'display: none;') ?>">
                                <img id="previewImg" src="<?= $post['featured_image'] ? (strpos($post['featured_image'], 'http') === 0 ? $post['featured_image'] : UPLOAD_URL . '/' . $post['featured_image']) : '' ?>" alt="Preview">
                                <button type="button" class="remove-preview" onclick="removeFeaturedImage()">&times;</button>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-actions mt-4">
                        <button type="submit" class="btn btn-primary btn-full">
                            Cập nhật bài viết
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Media Picker Modal -->
<div id="mediaPickerModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Chọn ảnh từ thư viện</h2>
            <button class="close-modal" onclick="closeMediaPicker()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="mediaGrid" class="media-picker-grid">
                <div class="loading">Đang tải...</div>
            </div>
        </div>
    </div>
</div>

<style>
    .post-editor-grid {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 24px;
        align-items: start;
    }

    .sticky-sidebar {
        position: sticky;
        top: 24px;
    }

    .required { color: #ef4444; }
    .mt-4 { margin-top: 1.5rem; }
    .mt-3 { margin-top: 1rem; }
    .mb-2 { margin-bottom: 0.5rem; }

    .divider {
        height: 1px;
        background: var(--border-color, #e5e7eb);
        margin: 20px 0;
    }

    .upload-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .featured-image-preview {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border-color, #e5e7eb);
    }

    .featured-image-preview img {
        width: 100%;
        display: block;
        max-height: 200px;
        object-fit: cover;
    }

    .remove-preview {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(0,0,0,0.5);
        color: white;
        border: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: background 0.2s;
    }

    .remove-preview:hover {
        background: rgba(220, 38, 38, 0.8);
    }

    .upload-progress-container {
        margin-top: 12px;
    }

    .progress-bar-bg {
        height: 6px;
        background: #f3f4f6;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: var(--primary-color, #2C5F4F);
        width: 0%;
        transition: width 0.3s ease;
    }

    .progress-message {
        display: block;
        margin-top: 4px;
        font-size: 0.8rem;
        color: #6b7280;
    }

    @media (max-width: 1024px) {
        .post-editor-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Hide CKEditor notification bar */
    .cke_notification_warning,
    .cke_notification {
        display: none !important;
    }
    
    /* Media Picker Modal */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 900px;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 20px;
        color: #333;
    }
    
    .close-modal {
        background: none;
        border: none;
        font-size: 32px;
        color: #999;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .close-modal:hover {
        background: #f5f5f5;
        color: #333;
    }
    
    .modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }
    
    .media-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 16px;
    }
    
    .media-picker-item {
        border: 2px solid #e5e5e5;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9f9f9;
    }
    
    .media-picker-item:hover {
        border-color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    
    .media-picker-item img {
        width: 100%;
        height: 140px;
        object-fit: cover;
        display: block;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
        color: #999;
    }
</style>


<!-- CKEditor 4 Full -->
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<script>
    // CKEditor Configuration
    CKEDITOR.config.notification_aggregationTimeout = 0;
    var editor = CKEDITOR.replace('content', {
        height: 500,
        removePlugins: 'exportpdf',
        filebrowserUploadUrl: '/admin/upload_ckeditor.php',
        filebrowserUploadMethod: 'xhr',
        uiColor: '#ffffff',
        toolbarGroups: [
            { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
            { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
            { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
            { name: 'forms', groups: [ 'forms' ] },
            '/',
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
            { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
            { name: 'links', groups: [ 'links' ] },
            { name: 'insert', groups: [ 'insert' ] },
            '/',
            { name: 'styles', groups: [ 'styles' ] },
            { name: 'colors', groups: [ 'colors' ] },
            { name: 'tools', groups: [ 'tools' ] },
            { name: 'others', groups: [ 'others' ] },
            { name: 'about', groups: [ 'about' ] }
        ],
        removeButtons: 'Save,NewPage,Preview,Print,Templates,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,About,ShowBlocks,Div',
        font_names: 'Arial/Arial, Helvetica, sans-serif;' +
                    'Comic Sans MS/Comic Sans MS, cursive;' +
                    'Courier New/Courier New, Courier, monospace;' +
                    'Georgia/Georgia, serif;' +
                    'Lucida Sans Unicode/Lucida Sans Unicode, Lucida Grande, sans-serif;' +
                    'Tahoma/Tahoma, Geneva, sans-serif;' +
                    'Times New Roman/Times New Roman, Times, serif;' +
                    'Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;' +
                    'Verdana/Verdana, Geneva, sans-serif'
    });

    // Auto-insert uploaded images into editor
    editor.on('fileUploadResponse', function(evt) {
        evt.stop();
        var data = evt.data;
        var xhr = data.fileLoader.xhr;
        var response = xhr.responseText.split('|');
        
        if (response[1]) {
            // Standard response format
            data.url = response[1];
        } else {
            // JSON response format
            try {
                var jsonResponse = JSON.parse(xhr.responseText);
                if (jsonResponse.uploaded && jsonResponse.url) {
                    data.url = jsonResponse.url;
                }
            } catch(e) {
                console.error('Failed to parse upload response:', e);
            }
        }
    });

    // Sidebar Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('post_type');
        const spotifyField = document.getElementById('spotifyField');
        const categorySelect = document.getElementById('category_id');
        const originalOptions = Array.from(categorySelect.options);

        function updateContext() {
            const type = typeSelect.value;
            if (spotifyField) spotifyField.style.display = type === 'podcast' ? 'block' : 'none';
            
            const currentVal = categorySelect.value;
            categorySelect.innerHTML = '';
            originalOptions.forEach(opt => {
                if (opt.value === "" || opt.getAttribute('data-type') === type || opt.getAttribute('data-type') === 'post') {
                    categorySelect.appendChild(opt.cloneNode(true));
                }
            });
            // Restore selection if valid
            let exists = false;
            for (let i = 0; i < categorySelect.options.length; i++) {
                if (categorySelect.options[i].value === currentVal) {
                    exists = true;
                    break;
                }
            }
            if (exists) {
                categorySelect.value = currentVal;
            } else {
                categorySelect.value = "";
            }
        }

        typeSelect.addEventListener('change', updateContext);
        updateContext(); // Initial run
    });

    // Image Management
    function updateImagePreview(url) {
        const preview = document.getElementById('imagePreview');
        const img = document.getElementById('previewImg');
        const baseUrl = '<?= UPLOAD_URL ?>';
        
        if (url && url.trim() !== '') {
            img.src = url.startsWith('http') ? url : `${baseUrl}/${url}`;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }

    function removeFeaturedImage() {
        document.getElementById('featured_image').value = '';
        document.getElementById('imagePreview').style.display = 'none';
    }

    // Media Picker
    document.getElementById('openMediaPicker').addEventListener('click', function(e) {
        e.preventDefault();
        openMediaPicker();
    });

    // Update preview when URL is manually entered
    document.getElementById('featured_image').addEventListener('input', function(e) {
        updateImagePreview(e.target.value);
    });

    function openMediaPicker() {
        document.getElementById('mediaPickerModal').style.display = 'flex';
        loadMediaFiles();
    }

    function closeMediaPicker() {
        document.getElementById('mediaPickerModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('mediaPickerModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeMediaPicker();
        }
    });

    async function loadMediaFiles() {
        const grid = document.getElementById('mediaGrid');
        grid.innerHTML = '<div class="loading">Đang tải...</div>';
        try {
            const res = await fetch('/api/media-list.php?limit=50');
            const result = await res.json();
            if (result.success && result.data.length > 0) {
                grid.innerHTML = ''; // Clear loading
                result.data.forEach(item => {
                    // Only show images
                    if (!['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(item.file_type)) {
                        return;
                    }
                    const div = document.createElement('div');
                    div.className = 'media-picker-item';
                    div.innerHTML = `<img src="<?= UPLOAD_URL ?>/${item.file_path}" alt="${item.original_filename}" title="${item.original_filename}">`;
                    div.addEventListener('click', () => selectImage(item.file_path));
                    grid.appendChild(div);
                });
            } else {
                grid.innerHTML = '<div class="loading">Chưa có ảnh nào trong thư viện</div>';
            }
        } catch (e) {
            console.error('Failed to load media:', e);
            grid.innerHTML = '<div class="loading">Lỗi khi tải ảnh</div>';
        }
    }

    function selectImage(url) {
        document.getElementById('featured_image').value = url;
        updateImagePreview(url);
        closeMediaPicker();
    }

    // Upload Handle
    const uploadBtn = document.getElementById('uploadFeaturedBtn');
    const fileInput = document.getElementById('featuredImageFile');
    
    uploadBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image')) {
            alert('Chỉ chấp nhận file ảnh!');
            return;
        }
        
        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('File quá lớn! Tối đa 10MB');
            return;
        }

        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');

        progressDiv.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.textContent = 'Đang tải lên...';
        uploadBtn.disabled = true;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch('/admin/upload_featured_image.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                progressBar.style.width = '100%';
                progressText.textContent = 'Thành công!';
                progressText.style.color = '#28a745';
                document.getElementById('featured_image').value = data.filename;
                updateImagePreview(data.filename);
                setTimeout(() => {
                    progressDiv.style.display = 'none';
                    progressText.style.color = '#666';
                }, 1500);
            } else {
                throw new Error(data.message || 'Upload thất bại');
            }
        } catch (err) {
            console.error('Upload error:', err);
            progressBar.style.width = '0%';
            progressText.textContent = 'Lỗi: ' + (err.message || 'Không xác định');
            progressText.style.color = '#ef4444';
            setTimeout(() => {
                progressDiv.style.display = 'none';
                progressText.style.color = '#666';
            }, 3000);
        } finally {
            uploadBtn.disabled = false;
            fileInput.value = ''; // Reset file input
        }
    });
    });

</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
