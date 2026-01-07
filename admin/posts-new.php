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
    
    // SEO Fields
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';

    // Validation
    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề bài viết.';
    } elseif (empty($content)) {
        $error = 'Nội dung bài viết không được để trống.';
    } elseif ($category_id === 0) {
        $error = 'Vui lòng chọn chuyên mục.';
    } else {
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
                <h3>Chuyên mục</h3>
                <div class="form-group">
                    <select id="category_id" name="category_id" required>
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
                    <small>Hoặc <a href="/admin/media.php" target="_blank">chọn từ thư viện</a></small>
                </div>
            </div>
        </div>
    </div>
</form>

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
    
    CKEDITOR.replace('content', {
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
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
