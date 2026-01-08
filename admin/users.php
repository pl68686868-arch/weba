<?php declare(strict_types=1);

/**
 * Users Management - Admin
 * 
 * View and manage system users
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

// Filters
$role = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if (!empty($role)) {
    $where[] = "role = :role";
    $params['role'] = $role;
}

if (!empty($search)) {
    $where[] = "(username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
    $params['search'] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalUsers = (int)$db->fetchColumn(
    "SELECT COUNT(*) FROM users {$whereClause}",
    $params
);

$totalPages = ceil($totalUsers / $perPage);

// Get users
$users = $db->fetchAll(
    "SELECT * FROM users 
     {$whereClause}
     ORDER BY created_at DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <h1>Users</h1>
        <a href="/admin/users-new.php" class="btn btn-primary">+ New User</a>
    </div>
    
    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="" class="filters-form">
            <div class="filter-group" style="flex: 1; min-width: 200px;">
                <label>Search:</label>
                <input type="text" name="search" class="form-input" placeholder="Search by name, email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group">
                <label>Role:</label>
                <select name="role" class="form-select">
                    <option value="">All</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="author" <?= $role === 'author' ? 'selected' : '' ?>>Author</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="/admin/users.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="table-card">
        <?php if (!empty($users)): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= escape($user['full_name']) ?></strong>
                                <br>
                                <small class="text-muted"><?= escape($user['email']) ?></small>
                                <br>
                                <small class="text-muted">@<?= escape($user['username']) ?></small>
                            </td>
                            <td>
                                <span class="role-badge role-<?= $user['role'] ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td><?= formatDate($user['created_at'], 'date') ?></td>
                            <td>
                                <a href="/admin/users-edit.php?id=<?= $user['id'] ?>" class="btn btn-small btn-secondary">
                                    Edit
                                </a>
                                <?php if ($user['id'] != $auth->getUserId()): ?>
                                    <button 
                                        class="btn btn-small btn-danger delete-user-btn"
                                        data-user-id="<?= $user['id'] ?>"
                                        data-user-name="<?= escape($user['full_name']) ?>"
                                        title="Delete user">
                                        Delete
                                    </button>
                                <?php endif; ?>
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
                        Page <?= $page ?> of <?= $totalPages ?> (<?= $totalUsers ?> total)
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
                <p>No users found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteUserModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <h3>Delete User</h3>
        <p>Are you sure you want to delete <strong class="modal-user-name"></strong>?</p>
        <div class="alert alert-info">
            Their posts will be reassigned to you.
        </div>
        <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        <div class="modal-actions">
            <button id="confirmDeleteUser" class="btn btn-danger">Delete User</button>
            <button id="cancelDeleteUser" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast" style="display: none;"></div>

<style>
.role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}
.role-admin { background: #fee2e2; color: #991b1b; }
.role-editor { background: #e0e7ff; color: #3730a3; }
.role-author { background: #d1fae5; color: #065f46; }

.text-muted { color: #6b7280; font-size: 0.9em; }

/* Reusing modal/toast styles from posts.php or admin-style.css (if moved there) */
/* We'll copy critical styles just in case */
.modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; }
.modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
.modal-content { position: relative; background: white; border-radius: 12px; padding: 32px; max-width: 480px; width: 90%; z-index: 1001; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.modal-actions { display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end; }
.text-danger { color: #DC3545; }

.toast { position: fixed; top: 24px; right: 24px; background: #2C5F4F; color: white; padding: 16px 24px; border-radius: 8px; z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.toast.error { background: #DC3545; }
</style>

<script>
(function() {
    const modal = document.getElementById('deleteUserModal');
    const confirmBtn = document.getElementById('confirmDeleteUser');
    const cancelBtn = document.getElementById('cancelDeleteUser');
    const overlay = modal.querySelector('.modal-overlay');
    const modalUserName = modal.querySelector('.modal-user-name');
    
    let currentUserId = null;
    let currentRow = null;
    
    function showToast(msg, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.className = `toast ${type}`;
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 3000);
    }
    
    function closeModal() {
        modal.style.display = 'none';
        currentUserId = null;
    }
    
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentUserId = this.dataset.userId;
            currentRow = this.closest('tr');
            modalUserName.textContent = this.dataset.userName;
            modal.style.display = 'flex';
        });
    });
    
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    confirmBtn.addEventListener('click', async () => {
        if (!currentUserId) return;
        
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Deleting...';
        
        try {
            const formData = new FormData();
            formData.append('id', currentUserId);
            
            const res = await fetch('/api/delete-user.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                if (currentRow) currentRow.remove();
                showToast(data.message);
                closeModal();
            } else {
                showToast(data.message, 'error');
            }
        } catch (e) {
            showToast('An error occurred', 'error');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Delete User';
        }
    });
})();
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
