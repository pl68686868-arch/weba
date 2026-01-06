<?php
declare(strict_types=1);

/**
 * 404 Error Page - Không tìm thấy trang
 * 
 * Custom 404 error page with search and suggestions
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

// Set 404 header
http_response_code(404);

// SEO Setup
$seo = new SEO();
$seo->setTitle('Không tìm thấy trang - 404')
    ->setDescription('Trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển.')
    ->setCanonical(SITE_URL . '/404.php')
    ->setOGType('website')
    ->setOGImage(DEFAULT_OG_IMAGE);

// Get popular posts for suggestions
$db = Database::getInstance();
$suggestions = [];
try {
    $suggestions = $db->fetchAll(
        "SELECT p.*, c.slug as category_slug 
         FROM posts p
         JOIN categories c ON p.category_id = c.id
         WHERE p.status = 'published'
         ORDER BY p.view_count DESC
         LIMIT 3"
    );
} catch (Exception $e) {
    // Silent fail
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">404</div>
            <h1>Không tìm thấy trang</h1>
            <p class="error-message">
                Trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển. 
                Có thể URL đã thay đổi hoặc bạn đã nhập sai địa chỉ.
            </p>
            
            <div class="error-actions">
                <a href="/" class="btn btn-primary">← Về trang chủ</a>
                <a href="/writing.php" class="btn btn-secondary">Xem tất cả bài viết</a>
            </div>
            
            <!-- Search -->
            <div class="error-search">
                <h2>Tìm kiếm nội dung</h2>
                <form action="/search.php" method="GET" class="search-form">
                    <input 
                        type="search" 
                        name="q" 
                        placeholder="Tìm kiếm bài viết..." 
                        class="search-input"
                        required
                    >
                    <button type="submit" class="search-button">Tìm kiếm</button>
                </form>
            </div>
            
            <!-- Popular Posts Suggestions -->
            <?php if (!empty($suggestions)): ?>
                <div class="error-suggestions">
                    <h2>Bài viết được đọc nhiều</h2>
                    <ul class="suggestions-list">
                        <?php foreach ($suggestions as $post): ?>
                            <li>
                                <a href="/post/<?= escape($post['slug']) ?>" class="suggestion-link">
                                    <span class="suggestion-title"><?= escape($post['title']) ?></span>
                                    <span class="suggestion-meta">
                                        <?= $post['reading_time'] ?> phút đọc
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: var(--space-6xl) 0;
    min-height: 60vh;
    display: flex;
    align-items: center;
}

.error-content {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

.error-icon {
    font-size: 6rem;
    font-weight: 700;
    font-family: var(--font-heading);
    color: var(--color-accent-light);
    line-height: 1;
    margin-bottom: var(--space-lg);
    opacity: 0.5;
}

.error-page h1 {
    font-size: clamp(2rem, 5vw, 2.5rem);
    margin-bottom: var(--space-lg);
}

.error-message {
    color: var(--color-text-secondary);
    line-height: 1.8;
    margin-bottom: var(--space-3xl);
}

.error-actions {
    display: flex;
    justify-content: center;
    gap: var(--space-md);
    flex-wrap: wrap;
    margin-bottom: var(--space-5xl);
}

/* Search */
.error-search {
    margin-bottom: var(--space-5xl);
}

.error-search h2 {
    font-size: 1.25rem;
    margin-bottom: var(--space-lg);
}

.search-form {
    display: flex;
    gap: var(--space-sm);
    max-width: 500px;
    margin: 0 auto;
}

.search-input {
    flex: 1;
    padding: var(--space-md) var(--space-lg);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-ui);
    font-size: 1rem;
}

.search-button {
    padding: var(--space-md) var(--space-xl);
    background: var(--color-accent-dark);
    color: white;
    border: none;
    border-radius: var(--radius-md);
    font-family: var(--font-ui);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-base);
}

.search-button:hover {
    background: var(--color-accent-medium);
}

/* Suggestions */
.error-suggestions h2 {
    font-size: 1.25rem;
    margin-bottom: var(--space-lg);
}

.suggestions-list {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.suggestions-list li {
    border-bottom: 1px solid var(--color-border);
}

.suggestions-list li:last-child {
    border-bottom: none;
}

.suggestion-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-lg);
    transition: var(--transition-base);
    gap: var(--space-lg);
}

.suggestion-link:hover {
    background: var(--color-bg-secondary);
}

.suggestion-title {
    flex: 1;
    color: var(--color-text-primary);
}

.suggestion-meta {
    font-family: var(--font-ui);
    font-size: 0.875rem;
    color: var(--color-text-tertiary);
    white-space: nowrap;
}
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
