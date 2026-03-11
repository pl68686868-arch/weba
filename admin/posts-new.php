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

// Fetch categories for dropdown
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");

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

    // Validation
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
        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = createSlug($title);
        } else {
            $slug = createSlug($slug);
        }

        // Ensure slug is unique
        $checkSlug = $db->fetchOne("SELECT id FROM posts WHERE slug = :slug", ['slug' => $slug]);
        if ($checkSlug) {
            $slug .= '-' . time(); // Append timestamp to make unique
        }

        try {
            $db->beginTransaction();

            $data = [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'excerpt' => $excerpt,
                'category_id' => $category_id,
                'author_id' => $_SESSION['user_id'],
                'post_type' => $post_type,
                'spotify_url' => $spotify_url,
                'featured_image' => $featured_image,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'published_at' => ($status === 'published') ? date('Y-m-d H:i:s') : null
            ];

            $postId = $db->insert('posts', $data);

            // Handle Tags
            if (!empty($tags_input)) {
                $tags = array_map('trim', explode(',', $tags_input));
                foreach ($tags as $tagName) {
                    if (empty($tagName)) continue;
                    
                    $tagSlug = createSlug($tagName);
                    // Check if tag exists
                    $existingTag = $db->fetchOne("SELECT id FROM tags WHERE slug = :slug", ['slug' => $tagSlug]);
                    
                    if ($existingTag) {
                        $tagId = $existingTag['id'];
                        // Increment usage count
                        $db->query("UPDATE tags SET usage_count = usage_count + 1 WHERE id = :id", ['id' => $tagId]);
                    } else {
                        // Create new tag
                        $tagId = $db->insert('tags', [
                            'name' => $tagName, 
                            'slug' => $tagSlug,
                            'usage_count' => 1
                        ]);
                    }

                    // Link post to tag
                    $db->insert('post_tags', ['post_id' => $postId, 'tag_id' => $tagId]);
                }
            }

            $db->commit();
            $success = 'Đã thêm bài viết thành công!';
            
            // Redirect to edit page or cleared form? Let's stay here with success message
            // or redirect to list. Let's redirect to list for now.
            redirect('/admin/posts.php');

        } catch (Exception $e) {
            $db->rollback();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>
    <div class="admin-page__header">
        <h1>Thêm Bài Viết Mới</h1>
        <a href="/admin/posts.php" class="btn btn-secondary">← Quay lại danh sách</a>
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
                        <input type="text" id="title" name="title" class="form-input" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" placeholder="Nhập tiêu đề hấp dẫn...">
                    </div>

                    <div class="form-group">
                        <label for="slug" class="form-label">Đường dẫn (Slug)</label>
                        <div class="slug-input-wrapper">
                            <input type="text" id="slug" name="slug" class="form-input" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" placeholder="tu-dong-tao-tu-tieu-de">
                        </div>
                        <small class="form-hint">Để trống sẽ tự động tạo từ tiêu đề.</small>
                    </div>

                    <div class="form-group">
                        <label for="content" class="form-label">Nội dung <span class="required">*</span></label>
                        <textarea id="content" name="content" rows="20" required class="content-editor"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="excerpt" class="form-label">Mô tả ngắn (Excerpt)</label>
                        <textarea id="excerpt" name="excerpt" class="form-input" rows="4" placeholder="Viết một đoạn tóm tắt ngắn về bài viết..."><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
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
                        <input type="text" id="meta_title" name="meta_title" class="form-input" value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>" placeholder="Tiêu đề hiển thị trên Google...">
                    </div>
                    <div class="form-group">
                        <label for="meta_description" class="form-label">Thẻ mô tả (Meta Description)</label>
                        <textarea id="meta_description" name="meta_description" class="form-input" rows="3" placeholder="Đoạn mô tả ngắn hiển thị trên kết quả tìm kiếm..."><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
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
                            <option value="draft" <?= (($_POST['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Bản nháp</option>
                            <option value="published" <?= (($_POST['status'] ?? '') === 'published') ? 'selected' : '' ?>>Công khai</option>
                            <option value="scheduled" <?= (($_POST['status'] ?? '') === 'scheduled') ? 'selected' : '' ?>>Lên lịch</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="post_type" class="form-label">Loại nội dung</label>
                        <select id="post_type" name="post_type" class="form-select">
                            <option value="post" <?= (($_POST['post_type'] ?? '') === 'post') ? 'selected' : '' ?>>Bài viết (Blog)</option>
                            <option value="podcast" <?= (($_POST['post_type'] ?? '') === 'podcast') ? 'selected' : '' ?>>Podcast</option>
                        </select>
                    </div>

                    <div class="form-group" id="spotifyField" style="display: none;">
                        <label for="spotify_url" class="form-label">Link Spotify</label>
                        <input type="text" id="spotify_url" name="spotify_url" class="form-input" value="<?= htmlspecialchars($_POST['spotify_url'] ?? '') ?>" placeholder="https://open.spotify.com/episode/...">
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Chuyên mục</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">-- Chọn chuyên mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?? 'post' ?>" <?= (($_POST['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tags" class="form-label">Thẻ (Tags)</label>
                        <input type="text" id="tags" name="tags" class="form-input" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>" placeholder="Cảm xúc, Chữa lành, ...">
                        <small class="form-hint">Phân cách bằng dấu phẩy.</small>
                    </div>

                    <div class="divider"></div>

                    <div class="form-group">
                        <label class="form-label">Ảnh đại diện</label>
                        <div class="featured-image-upload">
                            <input type="text" id="featured_image" name="featured_image" class="form-input mb-2" value="<?= htmlspecialchars($_POST['featured_image'] ?? '') ?>" placeholder="Nhập URL hoặc upload...">
                            
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

                            <div id="imagePreview" class="featured-image-preview mt-3" style="display: none;">
                                <img id="previewImg" src="" alt="Preview">
                                <button type="button" class="remove-preview" onclick="removeFeaturedImage()">&times;</button>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-actions mt-4">
                        <button type="submit" class="btn btn-primary btn-full">
                            Lưu bài viết
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
        removeButtons: 'Save,NewPage,Preview,Print,Templates,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,About,ShowBlocks,Div'
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
            categorySelect.value = currentVal;
        }

        typeSelect.addEventListener('change', updateContext);
        updateContext();
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
    function openMediaPicker() {
        document.getElementById('mediaPickerModal').style.display = 'flex';
        loadMediaFiles();
    }

    function closeMediaPicker() {
        document.getElementById('mediaPickerModal').style.display = 'none';
    }

    async function loadMediaFiles() {
        const grid = document.getElementById('mediaGrid');
        grid.innerHTML = '<div class="loading">Đang tải...</div>';
        try {
            const res = await fetch('/api/media-list.php?limit=50');
            const result = await res.json();
            if (result.success) {
                grid.innerHTML = result.data.map(item => `
                    <div class="media-picker-item" onclick="selectImage('${item.file_path}')">
                        <img src="<?= UPLOAD_URL ?>/${item.file_path}" alt="${item.original_filename}">
                    </div>
                `).join('');
            }
        } catch (e) {
            grid.innerHTML = '<div class="error">Lỗi khi tải ảnh</div>';
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

        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');

        progressDiv.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.textContent = 'Đang tải lên...';

        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch('/admin/upload_featured_image.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                progressBar.style.width = '100%';
                progressText.textContent = 'Thành công!';
                document.getElementById('featured_image').value = data.filename;
                updateImagePreview(data.filename);
                setTimeout(() => progressDiv.style.display = 'none', 1500);
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            progressText.textContent = 'Lỗi: ' + err.message;
            progressText.style.color = '#ef4444';
        }
    });

    // Check initial preview
    const currentImg = document.getElementById('featured_image').value;
    if (currentImg) updateImagePreview(currentImg);
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
