<?php declare(strict_types=1);

/**
 * Posts Listing - Admin
 * 
 * View and manage all posts
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

$db = Database::getInstance();

// Filters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "p.status = :status";
    $params['status'] = $status;
}

if (!empty($type)) {
    $where[] = "p.post_type = :type";
    $params['type'] = $type;
}

if (!empty($search)) {
    $where[] = "(p.title LIKE :search OR p.slug LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($categoryId > 0) {
    $where[] = "p.category_id = :categoryId";
    $params['categoryId'] = $categoryId;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalPosts = (int)$db->fetchColumn(
    "SELECT COUNT(*) FROM posts p {$whereClause}",
    $params
);

$totalPages = ceil($totalPosts / $perPage);

// Get posts
$posts = $db->fetchAll(
    "SELECT p.*, c.name as category_name, u.full_name as author_name
     FROM posts p
     JOIN categories c ON p.category_id = c.id
     JOIN users u ON p.author_id = u.id
     {$whereClause}
     ORDER BY p.updated_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// Get categories for filter
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name ASC");

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <h1><?= isset($_GET['type']) && $_GET['type'] === 'podcast' ? 'Podcast & Dự án' : 'Bài viết' ?></h1>
            <p>Quản lý và biên tập nội dung hệ thống.</p>
        </div>
        <div class="admin-header__actions" style="display: flex; gap: 12px;">
            <button id="bulkDeleteBtn" class="btn btn-danger" style="display: none;">Xóa mục đã chọn (<span id="selectedCount">0</span>)</button>
            <a href="/admin/posts-new.php<?= isset($_GET['type']) ? '?type=' . $_GET['type'] : '' ?>" class="btn btn-primary">
                <span>+</span> Tác phẩm mới
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card" style="padding: 20px; margin-bottom: 24px;">
        <form method="GET" action="" class="filters-form" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <?php if (isset($_GET['type'])): ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($_GET['type']) ?>">
            <?php endif; ?>
            
            <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Tiêu đề bài viết..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="form-group" style="width: 150px; margin-bottom: 0;">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Đã đăng</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Bản nháp</option>
                </select>
            </div>

            <?php if (!isset($_GET['type'])): ?>
            <div class="form-group" style="width: 150px; margin-bottom: 0;">
                <label class="form-label">Loại</label>
                <select name="type" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="post" <?= $type === 'post' ? 'selected' : '' ?>>Bài viết</option>
                    <option value="podcast" <?= $type === 'podcast' ? 'selected' : '' ?>>Podcast</option>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="form-group" style="width: 180px; margin-bottom: 0;">
                <label class="form-label">Chuyên mục</label>
                <select name="category" class="form-select">
                    <option value="">Tất cả</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId === $cat['id'] ? 'selected' : '' ?>>
                            <?= escape($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-secondary">Lọc</button>
                <a href="/admin/posts.php<?= isset($_GET['type']) ? '?type=' . $_GET['type'] : '' ?>" class="btn btn-secondary" title="Xóa lọc">↺</a>
            </div>
        </form>
    </div>
    
    <!-- Table -->
    <div class="table-card">
        <?php if (!empty($posts)): ?>
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll"></th>
                            <th>Nội dung</th>
                            <th>Chuyên mục</th>
                            <th>Thông số</th>
                            <th>Trạng thái</th>
                            <th>Cập nhật</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td style="text-align: center;"><input type="checkbox" class="post-checkbox" value="<?= $post['id'] ?>"></td>
                                <td>
                                    <div style="font-weight: 600; font-size: 1rem; margin-bottom: 4px;">
                                        <a href="/admin/posts-edit.php?id=<?= $post['id'] ?>" class="post-link">
                                            <?= escape($post['title']) ?>
                                        </a>
                                    </div>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <span style="font-size: 0.75rem; color: var(--text-muted); background: var(--bg-body); padding: 2px 6px; border-radius: 4px;">
                                            <?= ucfirst($post['post_type']) ?>
                                        </span>
                                        <a href="/post/<?= escape($post['slug']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--color-accent); font-weight: 500;">
                                            Xem trực tiếp ↗
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-main);">
                                        <?= escape($post['category_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--color-primary);"><?= number_format($post['view_count']) ?> <span style="font-size: 0.7rem; font-weight: 400; color: var(--text-muted); text-transform: uppercase;">view</span></span>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?= escape($post['author_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $post['status'] ?>">
                                        <?= $post['status'] === 'published' ? 'Đã đăng' : 'Bản nháp' ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                    <?= formatDate($post['updated_at'], 'relative') ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="/admin/posts-edit.php?id=<?= $post['id'] ?>" class="btn btn-secondary btn-small" title="Chỉnh sửa">
                                            Sửa
                                        </a>
                                        <button 
                                            class="btn btn-danger btn-small delete-post-btn"
                                            data-post-id="<?= $post['id'] ?>"
                                            data-post-title="<?= htmlspecialchars($post['title']) ?>"
                                            style="padding: 6px 12px; background: #FEF2F2;"
                                            title="Xóa bài">
                                            Xóa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 32px; padding: 0 8px;">
                    <div style="font-size: 0.875rem; color: var(--text-muted);">
                        Hiển thị trang <strong><?= $page ?></strong> trên <strong><?= $totalPages ?></strong> (Tổng <?= $totalPosts ?> mục)
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-secondary btn-small">
                                ← Trước
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-secondary btn-small">
                                Sau →
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;">📄</div>
                <h3 style="margin-bottom: 8px;">Không tìm thấy bài viết</h3>
                <p style="color: var(--text-muted); margin-bottom: 24px;">Hãy thử thay đổi bộ lọc hoặc tạo bài viết đầu tiên.</p>
                <a href="/admin/posts-new.php" class="btn btn-primary">Viết bài mới</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="card modal-content" style="max-width: 440px; border-radius: 20px; padding: 32px; box-shadow: 0 30px 60px rgba(0,0,0,0.3); border: none;">
        <div style="width: 60px; height: 60px; background: #FEF2F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <span style="font-size: 24px; color: #DC2626;">⚠️</span>
        </div>
        <h3 style="text-align: center; margin-bottom: 12px; font-size: 1.5rem;">Xác nhận xóa</h3>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 4px;">Bạn có chắc chắn muốn xóa vĩnh viễn bài đăng này?</p>
        <p class="modal-post-title" style="text-align: center; font-weight: 700; color: var(--text-main); font-size: 1.125rem; margin-bottom: 32px;"></p>
        
        <div style="display: flex; gap: 12px;">
            <button id="cancelDelete" class="btn btn-secondary" style="flex: 1; border-radius: 12px; height: 48px;">Hủy bỏ</button>
            <button id="confirmDelete" class="btn btn-danger" style="flex: 1; border-radius: 12px; height: 48px; background: #DC2626; color: white;">Xóa vĩnh viễn</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast" style="display: none; position: fixed; top: 32px; right: 32px; z-index: 10000; padding: 16px 24px; border-radius: 12px; color: white; box-shadow: var(--shadow-lg); font-weight: 500; min-width: 300px; animation: slideIn 0.3s ease-out;"></div>

<script>
// Post deletion functionality
(function() {
    const modal = document.getElementById('deleteModal');
    const confirmBtn = document.getElementById('confirmDelete');
    const cancelBtn = document.getElementById('cancelDelete');
    const modalOverlay = modal?.querySelector('.modal-overlay');
    const modalPostTitle = modal?.querySelector('.modal-post-title');
    
    let currentPostId = null;
    let currentPostRow = null;
    
    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = `toast ${type}`;
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }
    
    // Open modal
    function openModal(postId, postTitle, rowElement) {
        currentPostId = postId;
        currentPostRow = rowElement;
        modalPostTitle.textContent = postTitle;
        modal.style.display = 'flex';
    }
    
    // Close modal
    function closeModal() {
        modal.style.display = 'none';
        currentPostId = null;
        currentPostRow = null;
    }
    
    // Delete post
    async function deletePost() {
        if (!currentPostId) return;
        
        // Disable buttons during request
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Deleting...';
        
        try {
            const formData = new FormData();
            formData.append('id', currentPostId);
            
            const response = await fetch('/api/delete-post.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove row from table with animation
                if (currentPostRow) {
                    currentPostRow.style.opacity = '0';
                    currentPostRow.style.transform = 'scale(0.95)';
                    currentPostRow.style.transition = 'all 0.3s ease-out';
                    
                    setTimeout(() => {
                        currentPostRow.remove();
                        
                        // Check if table is now empty
                        const tbody = document.querySelector('.admin-table tbody');
                        if (tbody && tbody.children.length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    }, 300);
                }
                
                closeModal();
                showToast('Post deleted successfully', 'success');
            } else {
                showToast(data.message || 'Failed to delete post', 'error');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete Post';
            }
        } catch (error) {
            console.error('Delete error:', error);
            showToast('An error occurred while deleting the post', 'error');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Delete Post';
        }
    }
    
    // Event listeners
    document.querySelectorAll('.delete-post-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const postTitle = this.getAttribute('data-post-title');
            const row = this.closest('tr');
            openModal(postId, postTitle, row);
        });
    });
    
    cancelBtn?.addEventListener('click', closeModal);
    modalOverlay?.addEventListener('click', closeModal);
    confirmBtn?.addEventListener('click', deletePost);
    
    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });

    // Bulk Actions
    const selectAllDetails = document.getElementById('selectAll');
    const postCheckboxes = document.querySelectorAll('.post-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    
    function updateBulkUI() {
        const checked = document.querySelectorAll('.post-checkbox:checked');
        const count = checked.length;
        selectedCountSpan.textContent = count;
        if (bulkDeleteBtn) {
            bulkDeleteBtn.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }
    
    selectAllDetails?.addEventListener('change', function() {
        postCheckboxes.forEach(cb => cb.checked = this.checked);
        updateBulkUI();
    });
    
    postCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkUI);
    });
    
    bulkDeleteBtn?.addEventListener('click', async function() {
        const checked = document.querySelectorAll('.post-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        
        if (!confirm(`Are you sure you want to delete ${ids.length} posts? This cannot be undone.`)) {
            return;
        }
        
        this.disabled = true;
        this.textContent = 'Deleting...';
        
        try {
            const res = await fetch('/api/bulk-delete-posts.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            });
            
            const data = await res.json();
            
            if (data.success) {
                showToast(`Deleted ${data.deleted_count} posts successfully`);
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message, 'error');
                this.disabled = false; // Add reset text logic if needed
                updateBulkUI(); 
            }
        } catch (e) {
            console.error(e);
            showToast('An error occurred', 'error');
            this.disabled = false;
        }
    });
})();
</script>

<?php
// Include admin footer
include __DIR__ . '/../includes/admin-footer.php';
?>
