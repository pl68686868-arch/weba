<?php
declare(strict_types=1);

/**
 * Category Archive Page - Lưu trữ theo chuyên mục
 * 
 * Display posts filtered by category (4 pillars)
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Get category slug
$slug = $_GET['category'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

if (empty($slug)) {
    http_response_code(404);
    require '404.php';
    exit;
}

// Get category info
try {
    $category = $db->fetchOne(
        "SELECT * FROM categories WHERE slug = :slug LIMIT 1",
        ['slug' => $slug]
    );
    
    if (!$category) {
        http_response_code(404);
        require '404.php';
        exit;
    }
    
    // Get total posts count
    $totalPosts = (int)$db->fetchColumn(
        "SELECT COUNT(*) FROM posts WHERE category_id = :categoryId AND status = 'published'",
        ['categoryId' => $category['id']]
    );
    
    $totalPages = ceil($totalPosts / $perPage);
    
    // Get posts in this category
    $posts = $db->fetchAll(
        "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
         FROM posts p
         JOIN categories c ON p.category_id = c.id
         JOIN users u ON p.author_id = u.id
         WHERE p.category_id = :categoryId 
         AND p.status = 'published'
         ORDER BY p.published_at DESC
         LIMIT {$perPage} OFFSET {$offset}",
        ['categoryId' => $category['id']]
    );
    
    // SEO Setup
    $seo = new SEO();
    $pageTitle = $category['name'];
    if ($page > 1) {
        $pageTitle .= " - Trang {$page}";
    }
    
    $seo->setTitle($pageTitle)
        ->setDescription($category['meta_description'] ?? $category['description'] ?? '')
        ->setKeywords($category['meta_keywords'] ?? '')
        ->setCanonical(SITE_URL . '/category/' . $slug)
        ->setOGType('website')
        ->setOGImage(DEFAULT_OG_IMAGE);
    
    // Track page view
    trackPageView(null, '/category/' . $slug);
    
} catch (Exception $e) {
    error_log('Category page error: ' . $e->getMessage());
    http_response_code(500);
    echo "Đã có lỗi xảy ra. Vui lòng thử lại sau.";
    exit;
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="category-page">
    <div class="container">
        <!-- Breadcrumb -->
        <?= generateBreadcrumbs([
            ['name' => 'Trang chủ', 'url' => '/'],
            ['name' => 'Viết & Chia sẻ', 'url' => '/writing.php'],
            ['name' => $category['name'], 'url' => '']
        ]) ?>
        
        <header class="page-header">
            <h1><?= escape($category['name']) ?></h1>
            <?php if ($category['description']): ?>
                <p class="category-description"><?= escape($category['description']) ?></p>
            <?php endif; ?>
            <p class="category-count"><?= $totalPosts ?> bài viết</p>
        </header>
        
        <?php if (empty($posts)): ?>
            <div class="no-posts">
                <p>Chưa có bài viết nào trong chuyên mục này.</p>
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
                                <a href="/post/<?= escape($post['slug']) ?>">
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
                        <a href="?category=<?= escape($slug) ?>&page=<?= $page - 1 ?>" 
                           class="pagination__btn pagination__btn--prev">
                            ← Trang trước
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination__info">
                        Trang <?= $page ?> / <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?category=<?= escape($slug) ?>&page=<?= $page + 1 ?>" 
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
.category-page {
    padding: var(--space-4xl) 0;
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-5xl);
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.category-description {
    color: var(--color-text-secondary);
    line-height: 1.8;
    margin-top: var(--space-lg);
}

.category-count {
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
