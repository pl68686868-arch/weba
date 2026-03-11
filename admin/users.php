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
        <div>
            <h1>Quản lý người dùng</h1>
        </div>
        <div class="admin-page__actions">
            <a href="/admin/users-new.php" class="btn btn-primary">+ Thêm người dùng</a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <form method="GET" action="" class="filters-form-grid">
            <div class="form-group mb-0">
                <label class="form-label">Tìm kiếm</label>
                <div class="search-input-wrapper">
                    <input type="text" name="search" class="form-input" placeholder="Tên, email hoặc username..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            
            <div class="form-group mb-0">
                <label class="form-label">Vai trò</label>
                <select name="role" class="form-select">
                    <option value="">Tất cả vai trò</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                    <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Biên tập viên</option>
                    <option value="author" <?= $role === 'author' ? 'selected' : '' ?>>Tác giả</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-secondary">Lọc</button>
                <a href="/admin/users.php" class="btn btn-text">Xóa bộ lọc</a>
            </div>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <?php if (!empty($users)): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Vai trò</th>
                            <th>Ngày tham gia</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info-cell">
                                        <div class="user-avatar-placeholder">
                                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?= escape($user['full_name']) ?></div>
                                            <div class="user-meta">
                                                <span><?= escape($user['email']) ?></span>
                                                <span class="dot">•</span>
                                                <span class="user-handle">@<?= escape($user['username']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $user['role'] ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($user['created_at'], 'date') ?></td>
                                <td class="text-right">
                                    <div class="table-actions">
                                        <a href="/admin/users-edit.php?id=<?= $user['id'] ?>" class="btn-action" title="Chỉnh sửa">
                                            <i class="ph ph-pencil-simple"></i>
                                        </a>
                                        <?php if ($user['id'] != $auth->getUserId()): ?>
                                            <button 
                                                class="btn-action delete delete-user-btn"
                                                data-user-id="<?= $user['id'] ?>"
                                                data-user-name="<?= escape($user['full_name']) ?>"
                                                title="Xóa người dùng">
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
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-footer">
                    <div class="pagination-info">
                        Hiển thị trang <?= $page ?> / <?= $totalPages ?> (Tổng <?= $totalUsers ?> người dùng)
                    </div>
                    <div class="pagination-actions">
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
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <p>Không tìm thấy người dùng nào.</p>
                <a href="/admin/users-new.php" class="btn btn-primary mt-3">Thêm người dùng đầu tiên</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal & Toast are handled by global styles in admin-style.css -->

<style>
    .filters-form-grid {
        display: grid;
        grid-template-columns: 1fr 200px auto;
        gap: 20px;
        align-items: flex-end;
    }

    .filter-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .user-info-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar-placeholder {
        width: 40px;
        height: 40px;
        background: var(--primary-light, #f0f7f4);
        color: var(--primary-color, #2C5F4F);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 500;
        color: var(--text-color, #1f2937);
    }

    .user-meta {
        font-size: 0.85rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dot { font-size: 1.2rem; line-height: 1; }
    .user-handle { color: var(--primary-color, #2C5F4F); }

    .badge-admin { background: #fee2e2; color: #991b1b; }
    .badge-editor { background: #e0e7ff; color: #3730a3; }
    .badge-author { background: #d1fae5; color: #065f46; }

    .text-right { text-align: right; }
    
    .pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 24px;
        margin-top: 24px;
        border-top: 1px solid var(--border-color, #e5e7eb);
    }

    .pagination-info {
        font-size: 0.9rem;
        color: #6b7280;
    }

    .pagination-actions {
        display: flex;
        gap: 8px;
    }

    @media (max-width: 768px) {
        .filters-form-grid {
            grid-template-columns: 1fr;
        }
    }
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
