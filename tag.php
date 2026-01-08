<?php
declare(strict_types=1);

/**
 * Tag Archive Page - Lưu trữ theo tag
 * 
 * Display posts filtered by tag
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Get tag slug
$slug = $_GET['tag'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

if (empty($slug)) {
    http_response_code(404);
    require '404.php';
    exit;
}

// Get tag info
try {
    $tag = $db->fetchOne(
        "SELECT * FROM tags WHERE slug = :slug LIMIT 1",
        ['slug' => $slug]
    );
    
    if (!$tag) {
        http_response_code(404);
        require '404.php';
        exit;
    }
    
    // Get total posts count
    $totalPosts = (int)$db->fetchColumn(
        "SELECT COUNT(*) FROM post_tags pt
         JOIN posts p ON pt.post_id = p.id
         WHERE pt.tag_id = :tagId AND p.status = 'published'",
        ['tagId' => $tag['id']]
    );
    
    $totalPages = ceil($totalPosts / $perPage);
    
    // Get posts with this tag
    $posts = $db->fetchAll(
        "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
         FROM posts p
         JOIN post_tags pt ON p.id = pt.post_id
         JOIN categories c ON p.category_id = c.id
         JOIN users u ON p.author_id = u.id
         WHERE pt.tag_id = :tagId 
         AND p.status = 'published'
         ORDER BY p.published_at DESC
         LIMIT {$perPage} OFFSET {$offset}",
        ['tagId' => $tag['id']]
    );
    
    // SEO Setup
    $seo = new SEO();
    $pageTitle = "Tag: {$tag['name']}";
    if ($page > 1) {
        $pageTitle .= " - Trang {$page}";
    }
    
    $seo->setTitle($pageTitle)
        ->setDescription("Tất cả bài viết được gắn tag {$tag['name']}")
        ->setCanonical(SITE_URL . '/tag/' . $slug)
        ->setOGType('website')
        ->setOGImage(DEFAULT_OG_IMAGE);
    
    // Track page view
    trackPageView(null, '/tag/' . $slug);
    
} catch (Exception $e) {
    error_log('Tag page error: ' . $e->getMessage());
    http_response_code(500);
    echo "Đã có lỗi xảy ra. Vui lòng thử lại sau.";
    exit;
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="tag-page">
    <div class="container">
        <!-- Breadcrumb -->
        <?= generateBreadcrumbs([
            ['name' => 'Trang chủ', 'url' => '/'],
            ['name' => 'Viết & Chia sẻ', 'url' => '/writing.php'],
            ['name' => $tag['name'], 'url' => '']
        ]) ?>
        
        <header class="page-header">
            <div class="tag-badge">#</div>
            <h1><?= escape($tag['name']) ?></h1>
            <p class="tag-count"><?= $totalPosts ?> bài viết</p>
        </header>
        
        <?php if (empty($posts)): ?>
            <div class="no-posts">
                <p>Chưa có bài viết nào với tag này.</p>
                <a href="/writing.php" class="btn btn-secondary">← Xem tất cả bài viết</a>
            </div>
        <?php else: ?>
            <div class="articles-grid">
                <?php foreach ($posts as $post): ?>
                    <article class="article-card">
                        <?php if ($post['featured_image']): ?>
                            <img 
                                src="<?= UPLOAD_URL . '/' . escape($post['featured_image']) ?>" 
                                alt="<?= escape($post['title']) ?>"
                                class="article-card__image"
                                loading="lazy"
                            >
                        <?php endif; ?>
                        
                        <div class="article-card__content">
                            <a href="/category/<?= escape($post['category_slug']) ?>" class="article-card__category">
                                <?= escape($post['category_name']) ?>
                            </a>
                            
                            <h2 class="article-card__title">
                                <a href="/post.php?slug=<?= escape($post['slug']) ?>">
                                    <?= escape($post['title']) ?>
                                </a>
                            </h2>
                            
                            <?php if ($post['excerpt']): ?>
                                <p class="article-card__excerpt">
                                    <?= escape(generateExcerpt($post['excerpt'], 150)) ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="article-card__meta">
                                <span><?= formatDate($post['published_at'] ?? $post['created_at'], 'short') ?></span>
                                <span><?= $post['reading_time'] ?> phút đọc</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?tag=<?= escape($slug) ?>&page=<?= $page - 1 ?>" 
                           class="pagination__btn pagination__btn--prev">
                            ← Trang trước
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination__info">
                        Trang <?= $page ?> / <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?tag=<?= escape($slug) ?>&page=<?= $page + 1 ?>" 
                           class="pagination__btn pagination__btn--next">
                            Trang sau →
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.tag-page {
    padding: var(--space-4xl) 0;
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-5xl);
}

.tag-badge {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--space-lg);
    background: var(--color-accent-lighter);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-accent-dark);
}

.tag-count {
    font-family: var(--font-ui);
    font-size: 0.9375rem;
    color: var(--color-text-secondary);
    margin-top: var(--space-md);
}

.no-posts {
    text-align: center;
    padding: var(--space-5xl) 0;
}

.no-posts p {
    color: var(--color-text-secondary);
    margin-bottom: var(--space-xl);
}
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
