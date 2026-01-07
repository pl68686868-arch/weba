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
    
    // SEO Fields
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';

    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề bài viết.';
    } elseif (empty($content)) {
        $error = 'Nội dung bài viết không được để trống.';
    } elseif ($category_id === 0) {
        $error = 'Vui lòng chọn chuyên mục.';
    } else {
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

<div class="admin-header">
    <h1>Chỉnh sửa bài viết</h1>
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
        <div class="main-column">
            <div class="card">
                <div class="form-group">
                    <label for="title">Tiêu đề bài viết <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($post['title']) ?>">
                </div>

                <div class="form-group">
                    <label for="slug">Đường dẫn (Slug)</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($post['slug']) ?>">
                </div>

                <div class="form-group">
                    <label for="content">Nội dung <span class="required">*</span></label>
                    <textarea id="content" name="content" rows="20" required class="content-editor"><?= htmlspecialchars($post['content']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="excerpt">Mô tả ngắn (Excerpt)</label>
                    <textarea id="excerpt" name="excerpt" rows="4"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="card">
                <h3>SEO Metadata</h3>
                <div class="form-group">
                    <label for="meta_title">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" rows="3"><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="sidebar-column">
            <div class="card">
                <h3>Thao tác</h3>
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Bản nháp</option>
                        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Công khai</option>
                        <option value="scheduled" <?= $post['status'] === 'scheduled' ? 'selected' : '' ?>>Lên lịch</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-full">Cập nhật</button>
            </div>

            <div class="card">
                <h3>Chuyên mục</h3>
                <div class="form-group">
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Chọn chuyên mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $post['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card">
                <h3>Tags</h3>
                <div class="form-group">
                    <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($tagList) ?>">
                </div>
            </div>

            <div class="card">
                <h3>Ảnh đại diện</h3>
                <div class="form-group">
                    <input type="text" id="featured_image" name="featured_image" value="<?= htmlspecialchars($post['featured_image'] ?? '') ?>" placeholder="Nhập URL ảnh...">
                    <small>Hoặc <a href="/admin/media.php" target="_blank">chọn từ thư viện</a></small>
                    <?php if (!empty($post['featured_image'])): ?>
                        <div style="margin-top:10px;">
                            <img src="<?= htmlspecialchars($post['featured_image']) ?>" style="max-width:100%; border-radius:4px;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
    .grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
    .card { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 24px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input[type="text"], .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
    .btn-full { width: 100%; }
    .content-editor { font-family: monospace; line-height: 1.5; }
    @media (max-width: 768px) { .grid-layout { grid-template-columns: 1fr; } }
    
    /* Hide CKEditor notification bar */
    .cke_notification_warning,
    .cke_notification {
        display: none !important;
    }
</style>


<!-- CKEditor 4 Full -->
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<script>
    // Suppress notification bar
    CKEDITOR.config.notification_aggregationTimeout = 0;
    
    CKEDITOR.replace('content', {
        height: 600,
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
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
