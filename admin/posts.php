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
    <div class="admin-page__header">
        <h1>Posts</h1>
        <div style="display: flex; gap: 10px;">
            <button id="bulkDeleteBtn" class="btn btn-danger" style="display: none;">Delete Selected (<span id="selectedCount">0</span>)</button>
            <a href="/admin/posts-new.php" class="btn btn-primary">+ New Post</a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="" class="filters-form">
            <div class="filter-group" style="flex: 1; min-width: 200px;">
                <label>Search:</label>
                <input type="text" name="search" class="form-input" placeholder="Search by title..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Category:</label>
                <select name="category" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId === $cat['id'] ? 'selected' : '' ?>>
                            <?= escape($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="/admin/posts.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
    
    <!-- Posts Table -->
    <div class="table-card">
        <?php if (!empty($posts)): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><input type="checkbox" class="post-checkbox" value="<?= $post['id'] ?>"></td>
                            <td>
                                <strong><?= escape($post['title']) ?></strong>
                                <br>
                                <small>
                                    <a href="/post/<?= escape($post['slug']) ?>" target="_blank">
                                        View →
                                    </a>
                                </small>
                            </td>
                            <td><?= escape($post['category_name']) ?></td>
                            <td><?= escape($post['author_name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $post['status'] ?>">
                                    <?= ucfirst($post['status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($post['view_count']) ?></td>
                            <td><?= formatDate($post['updated_at'], 'relative') ?></td>
                            <td>
                                <a href="/admin/posts-edit.php?id=<?= $post['id'] ?>" class="btn btn-small btn-secondary">
                                    Edit
                                </a>
                                <button 
                                    class="btn btn-small btn-danger delete-post-btn"
                                    data-post-id="<?= $post['id'] ?>"
                                    data-post-title="<?= htmlspecialchars($post['title']) ?>"
                                    title="Delete post">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination__btn">
                            ← Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination__info">
                        Page <?= $page ?> of <?= $totalPages ?> (<?= $totalPosts ?> total)
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination__btn">
                            Next →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No posts found.</p>
                <a href="/admin/posts-new.php" class="btn btn-primary">Create your first post</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this post?</p>
        <p class="modal-post-title"></p>
        <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        <div class="modal-actions">
            <button id="confirmDelete" class="btn btn-danger">Delete Post</button>
            <button id="cancelDelete" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast" style="display: none;"></div>

<style>
.filters-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.filters-form {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #2D2D2D;
}

.empty-state {
    text-align: center;
    padding: 48px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 24px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #E0E0E0;
}

.pagination__btn {
    padding: 8px 16px;
    background: #F5F5F5;
    border-radius: 6px;
    text-decoration: none;
    color: #2C5F4F;
    font-weight: 500;
    transition: background 0.2s;
}

.pagination__btn:hover {
    background: #E0E0E0;
}

.pagination__info {
    color: #5A5A5A;
    font-size: 0.9375rem;
}

/* Delete Button */
.btn-danger {
    background: #DC3545;
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #C82333;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    padding: 32px;
    max-width: 480px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    z-index: 1001;
}

.modal-content h3 {
    margin: 0 0 16px 0;
    font-size: 1.5rem;
    color: #2D2D2D;
}

.modal-content p {
    margin: 8px 0;
    color: #5A5A5A;
}

.modal-post-title {
    font-weight: 600;
    color: #2D2D2D;
    font-size: 1.0625rem;
}

.text-danger {
    color: #DC3545 !important;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    justify-content: flex-end;
}

/* Toast Notification */
.toast {
    position: fixed;
    top: 24px;
    right: 24px;
    background: #2C5F4F;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    z-index: 2000;
    animation: slideIn 0.3s ease-out;
}

.toast.error {
    background: #DC3545;
}

.toast.success {
    background: #28A745;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

</style>

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
