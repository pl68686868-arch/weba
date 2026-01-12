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
                'status' => $status,
                'status' => $status,
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

<div class="admin-header">
    <h1>Thêm Bài Viết Mới</h1>
    <a href="/admin/posts.php" class="btn btn-outline">Quay lại</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="POST" action="" class="post-form">
    <div class="grid-layout">
        <!-- Main Content Column -->
        <div class="main-column">
            <div class="card">
                <div class="form-group">
                    <label for="title">Tiêu đề bài viết <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" placeholder="Nhập tiêu đề hấp dẫn...">
                </div>

                <div class="form-group">
                    <label for="slug">Đường dẫn (Slug)</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" placeholder="tu-dong-tao-tu-tieu-de">
                    <small>Để trống sẽ tự động tạo từ tiêu đề.</small>
                </div>

                <div class="form-group">
                    <label for="content">Nội dung <span class="required">*</span></label>
                    <textarea id="content" name="content" rows="20" required class="content-editor" placeholder="Viết nội dung bài viết ở đây..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="excerpt">Mô tả ngắn (Excerpt)</label>
                    <textarea id="excerpt" name="excerpt" rows="4"><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
                    <small>Hiển thị ở trang chủ và danh sách bài viết.</small>
                </div>
            </div>

            <!-- SEO Section -->
            <div class="card">
                <h3>Tối ưu hóa tìm kiếm (SEO)</h3>
                <div class="form-group">
                    <label for="meta_title">Thẻ tiêu đề (Meta Title)</label>
                    <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="meta_description">Thẻ mô tả (Meta Description)</label>
                    <textarea id="meta_description" name="meta_description" rows="3"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="sidebar-column">
            <div class="card">
                <h3>Đăng bài</h3>
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        <option value="draft" <?= (($_POST['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Bản nháp</option>
                        <option value="published" <?= (($_POST['status'] ?? '') === 'published') ? 'selected' : '' ?>>Công khai</option>
                        <option value="scheduled" <?= (($_POST['status'] ?? '') === 'scheduled') ? 'selected' : '' ?>>Lên lịch</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-full">Lưu bài viết</button>
            </div>
            
            <div class="card">
                <h3>Loại bài viết</h3>
                <div class="form-group">
                    <label for="post_type">Loại nội dung</label>
                    <select id="post_type" name="post_type" onchange="toggleSpotifyField()">
                        <option value="post" <?= (($_POST['post_type'] ?? '') === 'post') ? 'selected' : '' ?>>Bài viết (Blog)</option>
                        <option value="podcast" <?= (($_POST['post_type'] ?? '') === 'podcast') ? 'selected' : '' ?>>Podcast</option>
                    </select>
                </div>
                
                <div class="form-group" id="spotifyField" style="display: none;">
                    <label for="spotify_url">Link Spotify</label>
                    <input type="text" id="spotify_url" name="spotify_url" value="<?= htmlspecialchars($_POST['spotify_url'] ?? '') ?>" placeholder="https://open.spotify.com/episode/...">
                    <small>Nhập link tập podcast trên Spotify.</small>
                </div>
            </div>

            <div class="card">
                <h3>Chuyên mục</h3>
                <div class="form-group">
                    <select id="category_id" name="category_id">
                        <option value="">-- Chọn chuyên mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card">
                <h3>Tags</h3>
                <div class="form-group">
                    <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>" placeholder="Tam ly, Giao duc, ...">
                    <small>Phân cách bằng dấu phẩy.</small>
                </div>
            </div>

            <div class="card">
                <h3>Ảnh đại diện</h3>
                <div class="form-group">
                    <input type="text" id="featured_image" name="featured_image" value="<?= htmlspecialchars($_POST['featured_image'] ?? '') ?>" placeholder="Nhập URL ảnh...">
                    <div style="display: flex; gap: 12px; margin-top: 8px;">
                        <button type="button" id="uploadFeaturedBtn" class="btn btn-secondary" style="flex: 0;">
                            📤 Upload ảnh
                        </button>
                        <small style="align-self: center;">hoặc <a href="#" id="openMediaPicker" style="color: #2563eb; text-decoration: underline;">chọn từ thư viện</a></small>
                    </div>
                    <input type="file" id="featuredImageFile" accept="image/*" style="display: none;">
                    
                    <div id="uploadProgress" style="display: none; margin-top: 10px;">
                        <div style="background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                            <div id="progressBar" style="height: 4px; background: #2563eb; width: 0%; transition: width 0.3s;"></div>
                        </div>
                        <small id="progressText" style="color: #666; margin-top: 4px; display: block;"></small>
                    </div>

                    <div id="imagePreview" style="margin-top:10px; display:none;">
                        <img id="previewImg" src="" style="max-width:100%; border-radius:4px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

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
    .grid-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }
    
    .card {
        background: white;
        padding: 24px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }

    .form-group input[type="text"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        box-sizing: border-box; /* Fix overflow */
    }

    .content-editor {
        font-family: monospace;
        line-height: 1.5;
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

    .btn-full {
        width: 100%;
        text-align: center;
    }

    .required {
        color: red;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .grid-layout {
            grid-template-columns: 1fr;
        }
    }
</style>


<!-- CKEditor 4 Full -->
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<script>
    // Suppress notification bar
    CKEDITOR.config.notification_aggregationTimeout = 0;
    
    var editor = CKEDITOR.replace('content', {
        height: 600,
        // Helper to remove branding
        removePlugins: 'exportpdf',
        // Enable upload
        filebrowserUploadUrl: '/admin/upload_ckeditor.php',
        filebrowserUploadMethod: 'xhr', // Use JSON response
        // Styling to make it look modern
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
        // Make font list "Word-like"
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
    
    // ===== Media Picker Functionality =====
    
    // Open media picker
    document.getElementById('openMediaPicker').addEventListener('click', function(e) {
        e.preventDefault();
        openMediaPicker();
    });
    
    // Update preview when URL is manually entered
    document.getElementById('featured_image').addEventListener('input', function(e) {
        updateImagePreview(e.target.value);
    });
    
    function openMediaPicker() {
        const modal = document.getElementById('mediaPickerModal');
        modal.style.display = 'flex';
        loadMediaItems();
    }
    
    function closeMediaPicker() {
        const modal = document.getElementById('mediaPickerModal');
        modal.style.display = 'none';
    }
    
    // Close modal when clicking outside
    document.getElementById('mediaPickerModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeMediaPicker();
        }
    });
    
    async function loadMediaItems() {
        const grid = document.getElementById('mediaGrid');
        grid.innerHTML = '<div class="loading">Đang tải...</div>';
        
        try {
            const response = await fetch('/api/media-list.php?limit=50');
            const result = await response.json();
            
            if (result.success && result.data.length > 0) {
                renderMediaGrid(result.data);
            } else {
                grid.innerHTML = '<div class="loading">Chưa có ảnh nào trong thư viện</div>';
            }
        } catch (error) {
            console.error('Failed to load media:', error);
            grid.innerHTML = '<div class="loading">Lỗi khi tải ảnh</div>';
        }
    }
    
    function renderMediaGrid(items) {
        const grid = document.getElementById('mediaGrid');
        grid.innerHTML = '';
        
        const UPLOAD_URL = '<?= UPLOAD_URL ?>';
        
        items.forEach(item => {
            // Only show images
            if (!['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(item.file_type)) {
                return;
            }
            
            const div = document.createElement('div');
            div.className = 'media-picker-item';
            // Display with full URL but pass only filename to selectImage
            const displayUrl = UPLOAD_URL + '/' + item.file_path;
            div.innerHTML = `<img src="${displayUrl}" alt="${item.original_filename}" title="${item.original_filename}">`;
            div.addEventListener('click', () => selectImage(item.file_path)); // Store only filename
            grid.appendChild(div);
        });
    }
    
    function selectImage(url) {
        document.getElementById('featured_image').value = url;
        updateImagePreview(url);
        closeMediaPicker();
    }
    
    function updateImagePreview(url) {
        const preview = document.getElementById('imagePreview');
        const img = document.getElementById('previewImg');
        const UPLOAD_URL = '<?= UPLOAD_URL ?>';
        
        if (url && url.trim() !== '') {
            // Construct full URL if it's just a filename
            const fullUrl = url.startsWith('http') ? url : `${UPLOAD_URL}/${url}`;
            img.src = fullUrl;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
    
    // Featured Image Upload Functionality
    const uploadBtn = document.getElementById('uploadFeaturedBtn');
    const fileInput = document.getElementById('featuredImageFile');
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    uploadBtn.addEventListener('click', () => {
        fileInput.click();
    });
    
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
        
        // Show progress
        progressDiv.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.textContent = 'Đang upload...';
        uploadBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('file', file);
            
            const response = await fetch('/admin/upload_featured_image.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update progress to 100%
                progressBar.style.width = '100%';
                progressText.textContent = 'Upload thành công!';
                progressText.style.color = '#28a745';
                
                // Update input and preview with FILENAME ONLY
                document.getElementById('featured_image').value = data.filename;
                updateImagePreview(data.filename);
                
                // Hide progress after 2s
                setTimeout(() => {
                    progressDiv.style.display = 'none';
                    progressText.style.color = '#666';
                }, 2000);
            } else {
                throw new Error(data.message || 'Upload thất bại');
            }
        } catch (error) {
            console.error('Upload error:', error);
            progressBar.style.width = '0%';
            progressText.textContent = error.message || 'Lỗi khi upload ảnh';
            progressText.style.color = '#dc3545';
            
            setTimeout(() => {
                progressDiv.style.display = 'none';
                progressText.style.color = '#666';
            }, 3000);
        } finally {
            uploadBtn.disabled = false;
            fileInput.value = ''; // Reset file input
        }
    });

    // Toggle Spotify Field
    function toggleSpotifyField() {
        const type = document.getElementById('post_type').value;
        const field = document.getElementById('spotifyField');
        field.style.display = type === 'podcast' ? 'block' : 'none';
    }
    
    // Run on load
    toggleSpotifyField();

</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
