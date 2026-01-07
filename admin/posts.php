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
        <a href="/admin/posts-new.php" class="btn btn-primary">+ New Post</a>
    </div>
    
    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="" class="filters-form">
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
</style>

<?php
// Include admin footer
include __DIR__ . '/../includes/admin-footer.php';
?>
