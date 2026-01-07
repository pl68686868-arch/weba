<?php declare(strict_types=1);

/**
 * Admin Dashboard - Trang tổng quan
 * 
 * Analytics overview and quick actions
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

// Get statistics
try {
    $stats = [
        'total_posts' => $db->fetchColumn("SELECT COUNT(*) FROM posts"),
        'published_posts' => $db->fetchColumn("SELECT COUNT(*) FROM posts WHERE status = 'published'"),
        'draft_posts' => $db->fetchColumn("SELECT COUNT(*) FROM posts WHERE status = 'draft'"),
        'total_categories' => $db->fetchColumn("SELECT COUNT(*) FROM categories"),
        'total_tags' => $db->fetchColumn("SELECT COUNT(*) FROM tags"),
        'total_comments' => $db->fetchColumn("SELECT COUNT(*) FROM comments"),
        'pending_comments' => $db->fetchColumn("SELECT COUNT(*) FROM comments WHERE status = 'pending'"),
        'newsletter_subscribers' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'"),
    ];
    
    // Recent posts
    $recentPosts = $db->fetchAll(
        "SELECT p.*, c.name as category_name, u.full_name as author_name
         FROM posts p
         JOIN categories c ON p.category_id = c.id
         JOIN users u ON p.author_id = u.id
         ORDER BY p.updated_at DESC
         LIMIT 10"
    );
    
    // Popular posts this month
    $popularPosts = $db->fetchAll(
        "SELECT p.title, p.slug, p.view_count
         FROM posts p
         WHERE p.status = 'published'
         AND p.published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY p.view_count DESC
         LIMIT 5"
    );
    
} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $stats = [];
    $recentPosts = [];
    $popularPosts = [];
}

// Admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="dashboard">
    <div class="dashboard__header">
        <h1>Dashboard</h1>
        <p>Xin chào, <?= escape($auth->getUsername()) ?>!</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__icon">📄</div>
            <div class="stat-card__content">
                <div class="stat-card__value"><?= $stats['total_posts'] ?? 0 ?></div>
                <div class="stat-card__label">Tổng bài viết</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">✅</div>
            <div class="stat-card__content">
                <div class="stat-card__value"><?= $stats['published_posts'] ?? 0 ?></div>
                <div class="stat-card__label">Đã xuất bản</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">📝</div>
            <div class="stat-card__content">
                <div class="stat-card__value"><?= $stats['draft_posts'] ?? 0 ?></div>
                <div class="stat-card__label">Bản nháp</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">💬</div>
            <div class="stat-card__content">
                <div class="stat-card__value"><?= $stats['pending_comments'] ?? 0 ?></div>
                <div class="stat-card__label">Comments chờ duyệt</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">📧</div>
            <div class="stat-card__content">
                <div class="stat-card__value"><?= $stats['newsletter_subscribers'] ?? 0 ?></div>
                <div class="stat-card__label">Newsletter subscribers</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">🏷️</div>
            <div class="stat-card__content">
                <div class="stat-card__value"><?= $stats['total_tags'] ?? 0 ?></div>
                <div class="stat-card__label">Tags</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="/admin/posts-new.php" class="btn btn-primary">+ Tạo bài viết mới</a>
            <a href="/admin/posts.php" class="btn btn-secondary">Quản lý bài viết</a>
            <a href="/admin/comments.php" class="btn btn-secondary">Quản lý comments</a>
            <a href="/admin/media.php" class="btn btn-secondary">Media Library</a>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Recent Posts -->
        <div class="dashboard-section">
            <h2>Bài viết gần đây</h2>
            <?php if (!empty($recentPosts)): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Chuyên mục</th>
                            <th>Trạng thái</th>
                            <th>Cập nhật</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPosts as $post): ?>
                            <tr>
                                <td>
                                    <a href="/admin/posts-edit.php?id=<?= $post['id'] ?>">
                                        <?= escape($post['title']) ?>
                                    </a>
                                </td>
                                <td><?= escape($post['category_name']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $post['status'] ?>">
                                        <?= ucfirst($post['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($post['updated_at'], 'relative') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Chưa có bài viết nào.</p>
            <?php endif; ?>
        </div>
        
        <!-- Popular Posts -->
        <div class="dashboard-section">
            <h2>Bài viết phổ biến (30 ngày)</h2>
            <?php if (!empty($popularPosts)): ?>
                <ul class="popular-list">
                    <?php foreach ($popularPosts as $post): ?>
                        <li>
                            <a href="/post/<?= escape($post['slug']) ?>" target="_blank">
                                <?= escape($post['title']) ?>
                            </a>
                            <span class="view-count"><?= number_format($post['view_count']) ?> views</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Chưa có dữ liệu.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Admin footer
include __DIR__ . '/../includes/admin-footer.php';
?>
