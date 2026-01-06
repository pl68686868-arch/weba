<?php
declare(strict_types=1);

/**
 * Search Results Page - Tìm kiếm
 * 
 * Full-text search with filters
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Get search query
$query = trim($_GET['q'] ?? '');
$categorySlug = $_GET['category'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$results = [];
$totalResults = 0;

if (!empty($query) && strlen($query) >= 2) {
    // Build search conditions
    $where = ["p.status = 'published'"];
    $params = [];
    
    // Full-text search on title, excerpt, content
    $searchTerm = "%{$query}%";
    $where[] = "(p.title LIKE :search OR p.excerpt LIKE :search OR p.content LIKE :search)";
    $params['search'] = $searchTerm;
    
    // Category filter
    if (!empty($categorySlug)) {
        $where[] = "c.slug = :categorySlug";
        $params['categorySlug'] = $categorySlug;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM posts p 
                   JOIN categories c ON p.category_id = c.id 
                   WHERE " . implode(' AND ', $where);
    $totalResults = (int)$db->fetchColumn($countQuery, $params);
    
    // Get results
    if ($totalResults > 0) {
        $query = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
                  FROM posts p
                  JOIN categories c ON p.category_id = c.id
                  JOIN users u ON p.author_id = u.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY p.published_at DESC
                  LIMIT {$perPage} OFFSET {$offset}";
        
        $results = $db->fetchAll($query, $params);
    }
}

$totalPages = $totalResults > 0 ? ceil($totalResults / $perPage) : 0;

// SEO Setup
$seo = new SEO();
$pageTitle = !empty($query) ? "Tìm kiếm: {$query}" : "Tìm kiếm";
if ($page > 1) {
    $pageTitle .= " - Trang {$page}";
}

$seo->setTitle($pageTitle)
    ->setDescription("Kết quả tìm kiếm cho: {$query}")
    ->setCanonical(SITE_URL . '/search.php?q=' . urlencode($query))
    ->setOGType('website')
    ->setOGImage(DEFAULT_OG_IMAGE);

// Track search
if (!empty($query)) {
    trackPageView(null, '/search.php?q=' . urlencode($query));
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="search-page">
    <div class="container">
        <header class="page-header">
            <h1>Tìm kiếm</h1>
        </header>
        
        <!-- Search Form -->
        <form action="/search.php" method="GET" class="search-form-main">
            <input 
                type="search" 
                name="q" 
                placeholder="Tìm kiếm bài viết..." 
                class="search-input-main"
                value="<?= escape($query) ?>"
                required
                autofocus
            >
            <button type="submit" class="search-button-main">Tìm kiếm</button>
        </form>
        
        <?php if (!empty($query)): ?>
            <div class="search-results-info">
                <?php if ($totalResults > 0): ?>
                    <p>Tìm thấy <strong><?= $totalResults ?></strong> kết quả cho "<strong><?= escape($query) ?></strong>"</p>
                <?php else: ?>
                    <p>Không tìm thấy kết quả cho "<strong><?= escape($query) ?></strong>"</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($results)): ?>
                <div class="search-results">
                    <?php foreach ($results as $post): ?>
                        <article class="search-result">
                            <a href="/category/<?= escape($post['category_slug']) ?>" class="result-category">
                                <?= escape($post['category_name']) ?>
                            </a>
                            
                            <h2 class="result-title">
                                <a href="/post/<?= escape($post['slug']) ?>">
                                    <?= escape($post['title']) ?>
                                </a>
                            </h2>
                            
                            <?php if ($post['excerpt']): ?>
                                <p class="result-excerpt">
                                    <?= escape(generateExcerpt($post['excerpt'], 200)) ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="result-meta">
                                <span><?= formatDate($post['published_at'] ?? $post['created_at'], 'short') ?></span>
                                <span><?= $post['reading_time'] ?> phút đọc</span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination">
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
            <?php else: ?>
                <div class="no-results">
                    <h2>Gợi ý tìm kiếm</h2>
                    <ul>
                        <li>Thử sử dụng từ khóa khác</li>
                        <li>Kiểm tra chính tả</li>
                        <li>Sử dụng từ khóa chung hơn</li>
                        <li>Hoặc <a href="/writing.php">xem tất cả bài viết</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="search-placeholder">
                <p>Nhập từ khóa để tìm kiếm bài viết về tâm lý học, chánh niệm, giáo dục và phát triển con người.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-page {
    padding: var(--space-4xl) 0;
    min-height: 60vh;
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-3xl);
}

.search-form-main {
    max-width: 700px;
    margin: 0 auto var(--space-3xl);
    display: flex;
    gap: var(--space-sm);
}

.search-input-main {
    flex: 1;
    padding: var(--space-lg);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-ui);
    font-size: 1.125rem;
    transition: var(--transition-base);
}

.search-input-main:focus {
    outline: none;
    border-color: var(--color-accent-medium);
}

.search-button-main {
    padding: var(--space-lg) var(--space-2xl);
    background: var(--color-accent-dark);
    color: white;
    border: none;
    border-radius: var(--radius-md);
    font-family: var(--font-ui);
    font-weight: 500;
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition-base);
    white-space: nowrap;
}

.search-button-main:hover {
    background: var(--color-accent-medium);
}

.search-results-info {
    text-align: center;
    margin-bottom: var(--space-4xl);
    padding-bottom: var(--space-2xl);
    border-bottom: 1px solid var(--color-border);
}

.search-results-info p {
    font-family: var(--font-ui);
    color: var(--color-text-secondary);
}

.search-results {
    max-width: 800px;
    margin: 0 auto;
}

.search-result {
    padding: var(--space-xl) 0;
    border-bottom: 1px solid var(--color-border);
}

.search-result:last-child {
    border-bottom: none;
}

.result-category {
    font-family: var(--font-ui);
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-accent-medium);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--space-sm);
    display: inline-block;
}

.result-title {
    font-size: 1.5rem;
    margin-bottom: var(--space-md);
    line-height: 1.3;
}

.result-title a {
    color: var(--color-text-primary);
}

.result-title a:hover {
    color: var(--color-accent-dark);
}

.result-excerpt {
    color: var(--color-text-secondary);
    line-height: 1.6;
    margin-bottom: var(--space-md);
}

.result-meta {
    display: flex;
    gap: var(--space-lg);
    font-family: var(--font-ui);
    font-size: 0.875rem;
    color: var(--color-text-tertiary);
}

.no-results,
.search-placeholder {
    text-align: center;
    padding: var(--space-5xl) 0;
}

.no-results h2 {
    margin-bottom: var(--space-xl);
}

.no-results ul {
    list-style: none;
    padding: 0;
}

.no-results li {
    margin-bottom: var(--space-md);
    color: var(--color-text-secondary);
}

.search-placeholder p {
    color: var(--color-text-secondary);
    line-height: 1.8;
}
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
