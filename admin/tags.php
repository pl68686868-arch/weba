<?php declare(strict_types=1);

/**
 * Tags Management - Admin
 * 
 * Manage tags with CRUD operations
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SEO.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$error = '';
$success = '';

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
                    
                    if (empty($name)) {
                        $error = 'Tag name is required';
                    } else {
                        if (empty($slug)) {
                            $slug = SEO::createSlug($name);
                        }
                        
                        $db->insert('tags', [
                            'name' => $name,
                            'slug' => $slug
                        ]);
                        
                        $success = 'Tag created successfully';
                    }
                    break;
                    
                case 'update':
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    
                    if ($id > 0 && !empty($name)) {
                        $db->update(
                            'tags',
                            ['name' => $name, 'slug' => $slug],
                            'id = :id',
                            ['id' => $id]
                        );
                        
                        $success = 'Tag updated successfully';
                    }
                    break;
                    
                case 'delete':
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id > 0) {
                        // Delete tag associations first
                        $db->delete('post_tags', 'tag_id = :id', ['id' => $id]);
                        // Then delete tag
                        $db->delete('tags', 'id = :id', ['id' => $id]);
                        $success = 'Tag deleted successfully';
                    }
                    break;
                    
                case 'inline_update':
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    if ($id > 0 && !empty($name)) {
                        $db->update('tags', ['name' => $name], 'id = :id', ['id' => $id]);
                        $success = 'Tag name updated';
                    } else {
                        $error = 'Invalid data or empty name';
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log('Tags error: ' . $e->getMessage());
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

// Get all tags with post counts
$tags = $db->fetchAll(
    "SELECT t.*, COUNT(pt.post_id) as post_count 
     FROM tags t
     LEFT JOIN post_tags pt ON t.id = pt.tag_id
     GROUP BY t.id
     ORDER BY t.name ASC"
);

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1>Quản lý Thẻ (Tags)</h1>
            <p>Sử dụng các thẻ để giúp người đọc dễ dàng tìm thấy các bài viết liên quan.</p>
        </div>
        <div class="admin-page__actions">
            <button onclick="showCreateForm()" class="btn btn-primary">+ Thêm thẻ mới</button>
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
        <div class="modal-content max-w-sm">
            <h3 class="mb-6">🏷️ Tạo thẻ mới</h3>
            <form method="POST" action="">
                <?= $auth->getCSRFInput() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="grid grid-cols-2 gap-6">
                    <div class="form-group mb-0">
                        <label class="form-label">Tên thẻ *</label>
                        <input type="text" name="name" class="form-control" placeholder="Ví dụ: Chữa lành" required>
                    </div>
                    
                    <div class="form-group mb-0">
                        <label class="form-label">Slug (Tự động tạo nếu trống)</label>
                        <input type="text" name="slug" class="form-control" placeholder="chua-lanh">
                    </div>
                </div>
                
                <div class="form-actions mt-8">
                    <button type="button" onclick="hideCreateForm()" class="btn btn-secondary">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary">Tạo thẻ</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tags Grid -->
    <div class="tags-grid">
        <?php foreach ($tags as $tag): ?>
            <div class="card tag-card">
                <div class="tag-card-content">
                    <div class="tag-info">
                        <h3 class="tag-name inline-edit" data-id="<?= $tag['id'] ?>" title="Nhấn đúp để sửa" style="cursor: pointer;"><?= htmlspecialchars($tag['name']) ?></h3>
                        <code class="tag-slug">#<?= htmlspecialchars($tag['slug']) ?></code>
                    </div>
                    <span class="badge badge-published"><?= $tag['post_count'] ?> bài</span>
                </div>
                
                <div class="tag-card-actions">
                    <button onclick="editTag(<?= htmlspecialchars(json_encode($tag)) ?>)" class="btn-action" title="Sửa">
                        <i class="ph ph-pencil-simple"></i>
                    </button>
                    <button class="btn-action delete delete-tag" data-id="<?= $tag['id'] ?>" data-name="<?= escape($tag['name']) ?>" title="Xóa">
                        <i class="ph ph-trash"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($tags)): ?>
            <div class="empty-state">
                <div class="empty-icon">🏷️</div>
                <p>Chưa có thẻ nào được tạo. Hãy bắt đầu bằng cách thêm thẻ mới!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeEditModal()"></div>
    <div class="modal-content max-w-sm">
        <h3 class="mb-6">🏷️ Chỉnh sửa thẻ</h3>
        <form method="POST" action="">
            <?= $auth->getCSRFInput() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id">
            
            <div class="form-group">
                <label class="form-label">Tên thẻ *</label>
                <input type="text" id="edit_name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" id="edit_slug" name="slug" class="form-control">
            </div>
            
            <div class="form-actions mt-8">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Hủy bỏ</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<style>
    .tags-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .tag-card {
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin-bottom: 0;
    }

    .tag-card:hover {
        transform: translateY(-4px);
    }

    .tag-card-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .tag-name {
        margin: 0;
        font-size: 1.125rem;
        color: var(--color-primary);
    }

    .tag-slug {
        font-size: 0.75rem;
        color: var(--text-muted);
        background: var(--bg-body);
        padding: 2px 8px;
        border-radius: 6px;
        margin-top: 6px;
        display: inline-block;
    }

    .tag-card-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .max-w-sm { max-width: 440px; }
    .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .mb-0 { margin-bottom: 0; }
    .mt-8 { margin-top: 2rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mb-8 { margin-bottom: 2rem; }
    .inline-block { display: inline-block; }
    
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; }

    @media (max-width: 640px) {
        .grid-cols-2 { grid-template-columns: 1fr; }
    }
</style>

<style>
    .tag-item:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
</style>

<script>
function showCreateForm() {
    document.getElementById('createModal').style.display = 'flex';
}

function hideCreateForm() {
    document.getElementById('createModal').style.display = 'none';
}

function editTag(tag) {
    document.getElementById('edit_id').value = tag.id;
    document.getElementById('edit_name').value = tag.name;
    document.getElementById('edit_slug').value = tag.slug;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const escapeHtml = (unsafe) => unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");

    // UX: Optimistic Delete
    document.querySelectorAll('.delete-tag').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const name = this.dataset.name;
            const card = this.closest('.tag-card');
            
            Swal.fire({
                title: 'Xóa thẻ này?',
                text: `Chắc chắn xóa thẻ "${name}"? Thao tác này sẽ gỡ thẻ khỏi các bài viết liên quan.`,
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
                                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: data.message });
                                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                card.style.opacity = '0';
                                card.style.transform = 'scale(0.9)';
                                setTimeout(() => card.remove(), 300);
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
            input.style.padding = '2px 6px';
            input.style.marginTop = '-2px';
            input.style.marginBottom = '-2px';
            
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            
            const saveChanges = () => {
                const newName = input.value.trim();
                if (newName === currentName || newName === '') {
                    this.innerHTML = escapeHtml(currentName);
                    return;
                }
                
                this.innerHTML = `<span style="color:var(--color-accent); font-size: 0.875rem;">Đang lưu...</span>`;
                
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
                            this.innerHTML = escapeHtml(newName);
                            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, icon: 'success', title: 'Đã lưu' });
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
