<?php declare(strict_types=1);

/**
 * Categories Management - Admin
 * 
 * Manage the 4 pillars categories
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$db = Database::getInstance();
$error = '';
$success = '';

// AUTO-MIGRATION: Ensure 'type' column exists
try {
    $checkCol = $db->fetchAll("SHOW COLUMNS FROM categories LIKE 'type'");
    if (empty($checkCol)) {
        $db->query("ALTER TABLE categories ADD COLUMN type ENUM('post', 'podcast') DEFAULT 'post' AFTER slug");
    }
} catch (Exception $e) {
    // Ignore error if column exists or permission denied
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!$auth->validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            switch ($action) {
                case 'create':
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $type = $_POST['type'] ?? 'post'; // New Type Field
                    $description = trim($_POST['description'] ?? '');
                    $displayOrder = (int)($_POST['display_order'] ?? 0);
                    
                    if (empty($name)) {
                        $error = 'Category name is required';
                    } else {
                        if (empty($slug)) {
                            $slug = createSlug($name);
                        }
                        
                        $db->insert('categories', [
                            'name' => $name,
                            'slug' => $slug,
                            'type' => $type,
                            'description' => $description,
                            'display_order' => $displayOrder
                        ]);
                        
                        $success = 'Category created successfully';
                    }
                    break;
                    
                case 'update':
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $type = $_POST['type'] ?? 'post'; // New Type Field
                    $description = trim($_POST['description'] ?? '');
                    $displayOrder = (int)($_POST['display_order'] ?? 0);
                    
                    if ($id > 0 && !empty($name)) {
                        $db->update(
                            'categories',
                            [
                                'name' => $name,
                                'slug' => $slug,
                                'type' => $type,
                                'description' => $description,
                                'display_order' => $displayOrder
                            ],
                            'id = :id',
                            ['id' => $id]
                        );
                        
                        $success = 'Category updated successfully';
                    }
                    break;
                    
                case 'delete':
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id > 0) {
                        // Check if category has posts
                        $postCount = $db->fetchColumn(
                            "SELECT COUNT(*) FROM posts WHERE category_id = :id",
                            ['id' => $id]
                        );
                        
                        if ($postCount > 0) {
                            $error = "Cannot delete category with {$postCount} posts";
                        } else {
                            $db->delete('categories', 'id = :id', ['id' => $id]);
                            $success = 'Category deleted successfully';
                        }
                    }
                    break;
                    
                case 'inline_update':
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    if ($id > 0 && !empty($name)) {
                        $db->update('categories', ['name' => $name], 'id = :id', ['id' => $id]);
                        $success = 'Category name updated';
                    } else {
                        $error = 'Invalid data or empty name';
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log('Categories error: ' . $e->getMessage());
            $error = 'An error occurred';
        }
        
        // Handle AJAX Response
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => empty($error),
                'message' => $error ?: $success
            ]);
            exit;
        }
    }
}

// Get all categories
$categories = $db->fetchAll(
    "SELECT c.*, COUNT(p.id) as post_count 
     FROM categories c
     LEFT JOIN posts p ON c.id = p.category_id
     GROUP BY c.id
     ORDER BY c.type ASC, c.display_order ASC, c.name ASC"
);

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1>Danh mục & Trụ cột</h1>
            <p>Quản lý các chủ đề nội dung và phân loại bài viết.</p>
        </div>
        <div class="admin-page__actions">
            <button onclick="showCreateForm()" class="btn btn-primary">+ Thêm danh mục</button>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <span class="icon">⚠️</span> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <span class="icon">✅</span> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <!-- Create Modal -->
    <div id="createModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="hideCreateForm()"></div>
        <div class="modal-content max-w-2xl">
            <h3 class="mb-6">✨ Tạo danh mục mới</h3>
            <form method="POST" action="">
                <?= $auth->getCSRFInput() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="form-grid">
                    <div class="form-main">
                        <div class="form-group">
                            <label class="form-label">Tên danh mục *</label>
                            <input type="text" name="name" class="form-control" placeholder="Ví dụ: Thiền định & Chánh niệm" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Một vài dòng giới thiệu về danh mục này..."></textarea>
                        </div>
                    </div>
                    <div class="form-sidebar">
                        <div class="form-group">
                            <label class="form-label">Loại nội dung</label>
                            <select name="type" class="form-control">
                                <option value="post">Bài viết (Blog)</option>
                                <option value="podcast">Podcast</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug (Để trống để tự tạo)</label>
                            <input type="text" name="slug" class="form-control" placeholder="thien-dinh-chanh-niem">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Thứ tự hiển thị</label>
                            <input type="number" name="display_order" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions mt-6">
                    <button type="button" onclick="hideCreateForm()" class="btn btn-secondary">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary">Tạo danh mục</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Categories Table -->
    <div class="card p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="pl-6">Tên danh mục</th>
                        <th>Loại</th>
                        <th>Slug</th>
                        <th class="text-center">Số bài</th>
                        <th class="text-center">Thứ tự</th>
                        <th class="text-right pr-6">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="pl-6">
                                <div class="category-info">
                                    <div class="category-name inline-edit" data-id="<?= $cat['id'] ?>" title="Nhấn đúp để sửa" style="cursor: pointer;">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </div>
                                    <?php if ($cat['description']): ?>
                                        <div class="category-desc"><?= htmlspecialchars(substr($cat['description'], 0, 80)) ?>...</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $cat['type'] === 'podcast' ? 'published' : 'draft' ?>">
                                    <?= $cat['type'] === 'podcast' ? '🎧 Podcast' : '📖 Blog' ?>
                                </span>
                            </td>
                            <td><code class="slug-badge"><?= htmlspecialchars($cat['slug']) ?></code></td>
                            <td class="text-center font-bold"><?= $cat['post_count'] ?></td>
                            <td class="text-center text-muted"><?= $cat['display_order'] ?></td>
                            <td class="text-right pr-6">
                                <div class="table-actions">
                                    <button onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)" class="btn-action" title="Chỉnh sửa">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>
                                    <?php if ($cat['post_count'] == 0): ?>
                                        <button class="btn-action delete delete-category" data-id="<?= $cat['id'] ?>" data-name="<?= escape($cat['name']) ?>" title="Xóa">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeEditModal()"></div>
    <div class="modal-content max-w-2xl">
        <h3 class="mb-6">✏️ Chỉnh sửa danh mục</h3>
        <form method="POST" action="">
            <?= $auth->getCSRFInput() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id">
            
            <div class="form-group">
                <label class="form-label">Tên danh mục *</label>
                <input type="text" id="edit_name" name="name" class="form-control" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Loại</label>
                    <select id="edit_type" name="type" class="form-control">
                        <option value="post">Bài viết (Blog)</option>
                        <option value="podcast">Podcast</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Thứ tự</label>
                    <input type="number" id="edit_display_order" name="display_order" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" id="edit_slug" name="slug" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="form-actions mt-8">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Hủy bỏ</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; }
    .mb-8 { margin-bottom: 2rem; }
    .mt-6 { margin-top: 1.5rem; }
    .mt-8 { margin-top: 2rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .p-0 { padding: 0; }
    .pl-6 { padding-left: 1.5rem; }
    .pr-6 { padding-right: 1.5rem; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: 600; }
    .text-muted { color: var(--text-muted); }
    .inline-block { display: inline-block; }
    .max-w-2xl { max-width: 600px; }
    
    .category-info { display: flex; flex-direction: column; gap: 4px; }
    .category-name { font-weight: 600; color: var(--text-main); }
    .category-desc { font-size: 0.8125rem; color: var(--text-muted); line-height: 1.4; }
    
    .slug-badge { font-size: 0.75rem; background: var(--bg-body); padding: 4px 8px; border-radius: 6px; color: var(--text-muted); }
    
    .table-actions { display: flex; gap: 8px; justify-content: flex-end; }
    
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; }

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<script>
function showCreateForm() {
    document.getElementById('createModal').style.display = 'flex';
}

function hideCreateForm() {
    document.getElementById('createModal').style.display = 'none';
}

function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_name').value = cat.name;
    document.getElementById('edit_type').value = cat.type || 'post';
    document.getElementById('edit_slug').value = cat.slug;
    document.getElementById('edit_description').value = cat.description || '';
    document.getElementById('edit_display_order').value = cat.display_order;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    // Escape HTML Helper
    const escapeHtml = (unsafe) => unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");

    // UX: Optimistic Delete with SweetAlert2
    document.querySelectorAll('.delete-category').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const name = this.dataset.name;
            const tr = this.closest('tr');
            
            Swal.fire({
                title: 'Xóa danh mục?',
                text: `Chắc chắn xóa "${name}"? Thao tác này không thể hoàn tác.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#991B1B',
                cancelButtonColor: '#E6EDE9',
                cancelButtonText: '<span style="color:var(--color-primary)">Hủy</span>',
                confirmButtonText: 'Đồng ý xóa'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    formData.append('ajax', '1');
                    formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= escape($auth->generateCSRFToken()) ?>');
                    
                    fetch('', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                Swal.fire('Đã xóa', data.message, 'success');
                                tr.style.transition = 'opacity 0.3s ease';
                                tr.style.opacity = '0';
                                setTimeout(() => tr.remove(), 300);
                            } else {
                                Swal.fire('Lỗi', data.message, 'error');
                            }
                        })
                        .catch(() => Swal.fire('Lỗi', 'Lỗi kết nối.', 'error'));
                }
            });
        });
    });

    // UX: Inline Editing
    document.querySelectorAll('.inline-edit').forEach(el => {
        el.addEventListener('dblclick', function() {
            if (this.querySelector('input')) return;
            const id = this.dataset.id;
            const currentName = this.innerText.trim();
            
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentName;
            input.className = 'form-input';
            input.style.padding = '4px 8px';
            input.style.marginBottom = '-8px'; // Prevent row jumping
            input.style.marginTop = '-8px';
            
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            
            const saveChanges = () => {
                const newName = input.value.trim();
                if (newName === currentName || newName === '') {
                    this.innerHTML = escapeHtml(currentName);
                    return;
                }
                
                this.innerHTML = `<span style="color:var(--color-accent)">Đang lưu...</span>`;
                
                const formData = new FormData();
                formData.append('action', 'inline_update');
                formData.append('id', id);
                formData.append('name', newName);
                formData.append('ajax', '1');
                formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= escape($auth->generateCSRFToken()) ?>');
                
                fetch('', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            this.innerHTML = `<span style="color:var(--color-primary)">${escapeHtml(newName)}</span>`;
                            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Lưu thành công' });
                        } else {
                            this.innerHTML = escapeHtml(currentName);
                            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'error', title: data.message });
                        }
                    })
                    .catch(() => {
                        this.innerHTML = escapeHtml(currentName);
                    });
            };
            
            input.addEventListener('blur', saveChanges);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') saveChanges();
                if (e.key === 'Escape') this.innerHTML = escapeHtml(currentName);
            });
        });
    });
});
</script>

<?php
// Include admin footer
include __DIR__ . '/../includes/admin-footer.php';
?>
