<?php
declare(strict_types=1);

/**
 * Writing & Sharing Page - Viết & Chia sẻ
 * 
 * Article listing with advanced filtering by category and tags
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/Cache.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$cache = new Cache();

// Get filter parameters
$categorySlug = $_GET['category'] ?? '';
$tagSlug = $_GET['tag'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build query based on filters
$where = ["p.status = 'published'"];
$params = [];

// Category filter
$currentCategory = null;
if (!empty($categorySlug)) {
    $currentCategory = $db->fetchOne(
        "SELECT * FROM categories WHERE slug = :slug LIMIT 1",
        ['slug' => $categorySlug]
    );
    if ($currentCategory) {
        $where[] = "c.slug = :categorySlug";
        $params['categorySlug'] = $categorySlug;
    }
}

// Tag filter
$currentTag = null;
if (!empty($tagSlug)) {
    $currentTag = $db->fetchOne(
        "SELECT * FROM tags WHERE slug = :slug LIMIT 1",
        ['slug' => $tagSlug]
    );
    if ($currentTag) {
        // Need to join with post_tags
        $where[] = "EXISTS (SELECT 1 FROM post_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.post_id = p.id AND t.slug = :tagSlug)";
        $params['tagSlug'] = $tagSlug;
    }
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM posts p 
               JOIN categories c ON p.category_id = c.id 
               WHERE " . implode(' AND ', $where);
$totalPosts = (int)$db->fetchColumn($countQuery, $params);
$totalPages = ceil($totalPosts / $perPage);

// Get posts
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
          FROM posts p
          JOIN categories c ON p.category_id = c.id
          JOIN users u ON p.author_id = u.id
          WHERE " . implode(' AND ', $where) . "
          ORDER BY p.published_at DESC
          LIMIT {$perPage} OFFSET {$offset}";

$posts = $db->fetchAll($query, $params);

// Get all categories for filter
$categories = $cache->remember('writing_categories', function() use ($db) {
    return $db->fetchAll(
        "SELECT c.*, COUNT(p.id) as post_count 
         FROM categories c
         LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
         GROUP BY c.id
         ORDER BY c.display_order ASC"
    );
}, 3600);

// SEO Setup
$seo = new SEO();
$pageTitle = 'Viết & Chia sẻ';
$pageDescription = 'Không gian chia sẻ những suy tư và kiến thức về tâm lý học, chánh niệm, giáo dục người lớn và phản tư nghề nghiệp.';

if ($currentCategory) {
    $pageTitle = $currentCategory['name'];
    $pageDescription = $currentCategory['description'] ?? $currentCategory['meta_description'] ?? $pageDescription;
}
if ($currentTag) {
    $pageTitle = 'Tag: ' . $currentTag['name'];
}
if ($page > 1) {
    $pageTitle .= ' - Trang ' . $page;
}

$seo->setTitle($pageTitle)
    ->setDescription($pageDescription)
    ->setCanonical(SITE_URL . '/writing.php' . ($categorySlug ? '?category=' . $categorySlug : ''))
    ->setOGType('website')
    ->setOGImage(DEFAULT_OG_IMAGE);

// Track page view
trackPageView(null, '/writing.php' . ($categorySlug ? '?category=' . $categorySlug : ''));

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="writing-page">
    <div class="container">
        <!-- Page Header -->
        <header class="page-header">
            <h1><?= escape($pageTitle) ?></h1>
            <?php if (!$currentCategory && !$currentTag): ?>
                <p class="page-intro">
                    Trang Viết & Chia sẻ là không gian tôi lưu giữ và hệ thống hóa những bài viết được hình thành 
                    từ quá trình giảng dạy, thực hành tâm lý và phản tư nghề nghiệp. Các nội dung xoay quanh đời sống 
                    nội tâm của người trưởng thành, được tiếp cận từ góc nhìn tâm lý học ứng dụng, chánh niệm và giáo dục người lớn.
                </p>
            <?php elseif ($currentCategory): ?>
                <p class="page-intro"><?= escape($currentCategory['description']) ?></p>
            <?php endif; ?>
        </header>
        
        <!-- Category Filter -->
        <nav class="category-filter" aria-label="Lọc theo chủ đề">
            <a href="/writing.php" class="filter-btn <?= empty($categorySlug) ? 'active' : '' ?>">
                Tất cả
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="/writing.php?category=<?= escape($cat['slug']) ?>" 
                   class="filter-btn <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                    <?= escape($cat['name']) ?>
                    <span class="filter-count">(<?= $cat['post_count'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <!-- Posts Grid -->
        <?php if (empty($posts)): ?>
            <div class="no-posts">
                <div class="no-posts__icon">
                    <!-- Feather Icon -->
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"></path>
                        <line x1="16" y1="8" x2="2" y2="22"></line>
                        <line x1="17.5" y1="15" x2="9" y2="15"></line>
                    </svg>
                </div>
                <p>Chưa có bài viết nào trong chủ đề này.</p>
                <a href="/writing.php" class="btn btn-text">← Quay lại danh sách</a>
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
                <nav class="pagination" aria-label="Phân trang">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                           class="pagination__btn pagination__btn--prev">
                            ← Trang trước
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination__info">
                        Trang <?= $page ?> / <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                           class="pagination__btn pagination__btn--next">
                            Trang sau →
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
