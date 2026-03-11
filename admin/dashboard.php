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
    
    // Chart Data (Last 30 Days)
    $chartData = $db->fetchAll(
        "SELECT DATE(visited_at) as date, COUNT(*) as count 
         FROM page_views 
         WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
         GROUP BY DATE(visited_at) 
         ORDER BY date ASC"
    );
    
    // Fill missing days
    $dates = [];
    $counts = [];
    $period = new DatePeriod(
        new DateTime('-29 days'),
        new DateInterval('P1D'),
        new DateTime('+1 day')
    );
    
    foreach ($period as $date) {
        $dates[] = $date->format('Y-m-d');
        $counts[] = 0;
    }
    
    foreach ($chartData as $row) {
        $key = array_search($row['date'], $dates);
        if ($key !== false) {
            $counts[$key] = (int)$row['count'];
        }
    }
    
} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $stats = [];
    $recentPosts = [];
    $popularPosts = [];
    $dates = [];
    $counts = [];
}

// Admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="dashboard">
    <div class="admin-header">
        <div>
            <h1>Tổng quan</h1>
            <p>Xin chào, <?= escape($auth->getUsername()) ?>! Hôm nay là <?= date('d/m/Y') ?>.</p>
        </div>
        <div class="admin-header__actions">
            <a href="/admin/posts-new.php" class="btn btn-primary">
                <span>+</span> Viết bài mới
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__icon">📄</div>
            <div class="stat-card__label">Tổng bài viết</div>
            <div class="stat-card__value"><?= $stats['total_posts'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">✅</div>
            <div class="stat-card__label">Đã xuất bản</div>
            <div class="stat-card__value"><?= $stats['published_posts'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">📝</div>
            <div class="stat-card__label">Bản nháp</div>
            <div class="stat-card__value"><?= $stats['draft_posts'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">💬</div>
            <div class="stat-card__label">Chờ duyệt</div>
            <div class="stat-card__value"><?= $stats['pending_comments'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon">📧</div>
            <div class="stat-card__label">Bản tin</div>
            <div class="stat-card__value"><?= $stats['newsletter_subscribers'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Analytics Chart -->
    <div class="card chart-card">
        <h3>📈 Traffic (30 ngày gần đây)</h3>
        <div style="height: 350px; position: relative;">
            <canvas id="viewsChart"></canvas>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Recent Posts -->
        <div class="card">
            <h3>🖋️ Bài viết gần đây</h3>
            <?php if (!empty($recentPosts)): ?>
                <div class="table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th>Trạng thái</th>
                                <th>Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $post): ?>
                                <tr>
                                    <td>
                                        <a href="/admin/posts-edit.php?id=<?= $post['id'] ?>" style="font-weight: 500;">
                                            <?= escape($post['title']) ?>
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                            <?= escape($post['category_name']) ?>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 40px 0;">Chưa có bài viết nào.</p>
            <?php endif; ?>
            <div style="margin-top: 24px; text-align: center;">
                <a href="/admin/posts.php" class="btn btn-secondary btn-small">Xem tất cả bài viết</a>
            </div>
        </div>
        
        <!-- Popular Posts -->
        <div class="card">
            <h3>🔥 Bài viết phổ biến</h3>
            <?php if (!empty($popularPosts)): ?>
                <ul class="popular-list" style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($popularPosts as $post): ?>
                        <li style="padding: 16px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; padding-right: 16px;">
                                <a href="/post/<?= escape($post['slug']) ?>" target="_blank" style="font-weight: 500; display: block; margin-bottom: 4px;">
                                    <?= escape($post['title']) ?>
                                </a>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    /post/<?= escape($post['slug']) ?>
                                </span>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-family: var(--font-heading); font-weight: 700; color: var(--color-primary);"><?= number_format($post['view_count']) ?></div>
                                <div style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">lượt xem</div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 40px 0;">Chưa có dữ liệu bài viết phổ biến.</p>
            <?php endif; ?>
            <div style="margin-top: 24px; text-align: center;">
                <a href="/admin/media.php" class="btn btn-secondary btn-small">Thư viện Media</a>
            </div>
        </div>
    </div>
</div>

<?php
// Admin footer
include __DIR__ . '/../includes/admin-footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('viewsChart').getContext('2d');
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Lượt xem',
                data: <?= json_encode($counts) ?>,
                borderColor: '#2C5F4F',
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return null;
                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(0, 'rgba(44, 95, 79, 0)');
                    gradient.addColorStop(1, 'rgba(44, 95, 79, 0.15)');
                    return gradient;
                },
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointBackgroundColor: '#2C5F4F',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1C1F1D',
                    padding: 12,
                    titleFont: { size: 14, family: "'Inter', sans-serif" },
                    bodyFont: { size: 13, family: "'Inter', sans-serif" },
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 7,
                        font: { size: 11 }
                    }
                }
            }
        }
    });
});
</script>
