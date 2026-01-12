<?php
declare(strict_types=1);

/**
 * Single Post Page - Chi tiết bài viết
 * 
 * Premium reading experience with:
 * - Table of contents
 * - Reading progress
 * - Related articles
 * - Social sharing
 * - Comments
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/Cache.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/Auth.php';

$auth = new Auth();
$isAdmin = $auth->isAdmin();

$db = Database::getInstance();
$cache = new Cache();

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    require '404.php';
    exit;
}

// Get post data
try {
    $post = $db->fetchOne(
        "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name, u.email as author_email
         FROM posts p
         JOIN categories c ON p.category_id = c.id
         JOIN users u ON p.author_id = u.id
         WHERE p.slug = :slug " .
         ($isAdmin ? "" : "AND p.status = 'published'") .
         " LIMIT 1",
        ['slug' => $slug]
    );
    
    if (!$post) {
        http_response_code(404);
        require '404.php';
        exit;
    }
    
    // Get post tags
    $tags = $db->fetchAll(
        "SELECT t.* FROM tags t
         JOIN post_tags pt ON t.id = pt.tag_id
         WHERE pt.post_id = :postId
         ORDER BY t.name",
        ['postId' => $post['id']]
    );
    
    // Get related posts
    $relatedPosts = getRelatedPosts((int)$post['id'], (int)$post['category_id'], 3);
    
    // SEO Setup
    $seo = new SEO();
    $seo->setTitle($post['meta_title'] ?? $post['title'])
        ->setDescription($post['meta_description'] ?? generateExcerpt($post['excerpt'] ?? $post['content'], 160))
        ->setKeywords($post['meta_keywords'] ?? '')
        ->setCanonical($post['canonical_url'] ?? SITE_URL . '/post/' . $slug)
        ->setOGType('article')
        ->setOGImage($post['featured_image'] ? UPLOAD_URL . '/' . $post['featured_image'] : DEFAULT_OG_IMAGE)
        ->generateArticleSchema([
            'title' => $post['title'],
            'excerpt' => $post['excerpt'] ?? '',
            'content' => $post['content'],
            'author_name' => $post['author_name'],
            'published_at' => $post['published_at'],
            'created_at' => $post['created_at'],
            'updated_at' => $post['updated_at'],
            'featured_image' => $post['featured_image'],
            'category_name' => $post['category_name']
        ])
        ->generateBreadcrumbSchema([
            ['name' => 'Trang chủ', 'url' => SITE_URL . '/'],
            ['name' => $post['category_name'], 'url' => SITE_URL . '/category/' . $post['category_slug']],
            ['name' => $post['title'], 'url' => SITE_URL . '/post/' . $slug]
        ]);
    
    // Track page view
    trackPageView((int)$post['id'], '/post/' . $slug);
    
} catch (Exception $e) {
    error_log('Post page error: ' . $e->getMessage());
    http_response_code(500);
    echo "Đã có lỗi xảy ra. Vui lòng thử lại sau.";
    exit;
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<article class="single-post">
    <div class="container">
        <!-- Breadcrumb -->
        <?= generateBreadcrumbs([
            ['name' => 'Trang chủ', 'url' => '/'],
            ['name' => $post['category_name'], 'url' => '/category/' . $post['category_slug']],
            ['name' => $post['title'], 'url' => '']
        ]) ?>

        <?php if ($post['status'] !== 'published'): ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                <strong>Preview Mode:</strong> This post is currently <strong><?= ucfirst($post['status']) ?></strong>.
            </div>
        <?php endif; ?>
        
        <div class="post-layout">
            <!-- Main Content -->
            <div class="post-main">
                <header class="post-header">
                    <a href="/category/<?= escape($post['category_slug']) ?>" class="post-category">
                        <?= escape($post['category_name']) ?>
                    </a>
                    
                    <h1 class="post-title"><?= escape($post['title']) ?></h1>
                    
                    <div class="post-meta">
                        <span class="post-meta__author">Bởi <?= escape($post['author_name']) ?></span>
                        <span class="post-meta__date"><?= formatDate($post['published_at'] ?? $post['created_at'], 'long') ?></span>
                        <span class="post-meta__reading-time"><?= $post['reading_time'] ?> phút đọc</span>
                    </div>
                    
                    <?php if (!empty($post['featured_image'])): ?>
                        <?php
                            $featImg = $post['featured_image'];
                            // Check if it's an external URL
                            if (!preg_match("~^(?:f|ht)tps?://~i", $featImg)) {
                                $featImg = UPLOAD_URL . '/' . $featImg;
                            }
                        ?>
                        <div class="post-featured-image">
                            <img 
                                src="<?= escape($featImg) ?>" 
                                alt="<?= escape($post['title']) ?>"
                                loading="eager"
                                onerror="this.style.display='none'"
                            >
                        </div>
                    <?php endif; ?>
                </header>
                
                <?php if (!empty($post['spotify_url'])): ?>
                    <?php
                    // Convert standard Spotify URL to Embed URL if needed
                    $spotifyUrl = $post['spotify_url'];
                    if (strpos($spotifyUrl, 'open.spotify.com/episode') !== false && strpos($spotifyUrl, 'open.spotify.com/embed') === false) {
                        $spotifyUrl = str_replace('open.spotify.com/episode', 'open.spotify.com/embed/episode', $spotifyUrl);
                    }
                    ?>
                    <div class="podcast-player" style="margin-bottom: 3rem; margin-top: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden;">
                        <iframe style="border-radius:12px" src="<?= escape($spotifyUrl) ?>" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                    </div>
                <?php endif; ?>
                
                
                <div class="article-content">
                    <?= $post['content'] ?>
                </div>
                
                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                    <div class="post-tags">
                        <h3>Từ khóa</h3>
                        <div class="tag-list">
                            <?php foreach ($tags as $tag): ?>
                                <a href="/tag/<?= escape($tag['slug']) ?>" class="tag">
                                    <?= escape($tag['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Share Buttons -->
                <div class="post-share">
                    <h3>Chia sẻ bài viết</h3>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/post/' . $slug) ?>" 
                           target="_blank" 
                           class="share-btn share-btn--facebook"
                           data-share="facebook"
                           data-post-id="<?= $post['id'] ?>">
                            Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/post/' . $slug) ?>&text=<?= urlencode($post['title']) ?>" 
                           target="_blank" 
                           class="share-btn share-btn--twitter"
                           data-share="twitter"
                           data-post-id="<?= $post['id'] ?>">
                            Twitter
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode(SITE_URL . '/post/' . $slug) ?>&title=<?= urlencode($post['title']) ?>" 
                           target="_blank" 
                           class="share-btn share-btn--linkedin"
                           data-share="linkedin"
                           data-post-id="<?= $post['id'] ?>">
                            LinkedIn
                        </a>
                        <a href="mailto:?subject=<?= urlencode($post['title']) ?>&body=<?= urlencode(SITE_URL . '/post/' . $slug) ?>" 
                           class="share-btn share-btn--email"
                           data-share="email"
                           data-post-id="<?= $post['id'] ?>">
                            Email
                        </a>
                    </div>
                </div>
                
                <!-- Author Bio -->
                <div class="author-bio">
                    <h3>Về tác giả</h3>
                    <div class="author-bio__content">
                        <div class="author-bio__text">
                            <h4><?= escape($post['author_name'] ?? 'Admin') ?></h4>
                            <p><?= defined('SITE_TAGLINE') ? escape(SITE_TAGLINE) : 'Chia sẻ kiến thức & trải nghiệm' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <aside class="post-sidebar">
                <!-- Table of Contents (will be generated by JS) -->
                <div class="table-of-contents">
                    <h3>Nội dung</h3>
                    <!-- Generated by JavaScript -->
                </div>
            </aside>
        </div>
        
        <!-- Related Posts -->
        <?php if (!empty($relatedPosts) && is_array($relatedPosts)): ?>
            <section class="related-posts section-spacing">
                <div class="section-header text-center" style="margin-bottom: 2rem;">
                    <span class="eyebrow">Khám phá thêm</span>
                    <h2 class="section-title">Bài viết liên quan</h2>
                </div>
                <div class="articles-grid">
                    <?php foreach ($relatedPosts as $related): ?>
                        <article class="article-card" style="border-bottom: none;">
                            <?php if ($related['featured_image']): ?>
                                <?php
                                    $relImg = $related['featured_image'];
                                    if (!preg_match("~^(?:f|ht)tps?://~i", $relImg)) {
                                        $relImg = UPLOAD_URL . '/' . $relImg;
                                    }
                                ?>
                                <a href="/post/<?= escape($related['slug']) ?>" class="article-card__link" style="text-decoration: none;">
                                    <div class="article-card__image-wrapper" style="aspect-ratio: 16/10; overflow: hidden; border-radius: 8px; margin-bottom: 1rem;">
                                        <img 
                                            src="<?= escape($relImg) ?>" 
                                            alt="<?= escape($related['title']) ?>"
                                            class="article-card__image"
                                            loading="lazy"
                                            onerror="this.style.display='none'"
                                            style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;"
                                        >
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <div class="article-card__content">
                                <a href="/category/<?= escape($related['category_slug']) ?>" class="article-card__category" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-gold); font-weight: 600; text-decoration: none; display: block; margin-bottom: 0.5rem;">
                                    <?= escape($related['category_name']) ?>
                                </a>
                                
                                <h3 class="article-card__title" style="font-size: 1.25rem; margin: 0 0 0.5rem 0; line-height: 1.3;">
                                    <a href="/post/<?= escape($related['slug']) ?>" style="text-decoration: none; color: var(--color-text-primary);">
                                        <?= escape($related['title']) ?>
                                    </a>
                                </h3>
                                
                                <div class="article-card__meta" style="font-size: 0.85rem; color: #666; font-family: var(--font-ui);">
                                    <span><?= formatDate($related['published_at'] ?? $related['created_at'], 'short') ?></span>
                                    <span style="margin: 0 5px;">•</span>
                                    <span><?= $related['reading_time'] ?> phút đọc</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</article>

<style>
/* Post Layout */
.post-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-4xl);
    margin-top: var(--space-3xl);
}

@media (min-width: 1024px) {
    .post-layout {
        grid-template-columns: 1fr 300px;
    }
}

.post-header {
    text-align: center;
    margin-bottom: var(--space-xl);
    max-width: var(--container-narrow);
    margin-left: auto;
    margin-right: auto;
}

.post-category {
    font-family: var(--font-ui);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-accent-medium);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: var(--space-md);
    display: inline-block;
}

.post-title {
    font-size: clamp(2rem, 5vw, 2.5rem);
    line-height: 1.2;
    margin-bottom: var(--space-xl);
}

.post-meta {
    display: flex;
    justify-content: center; /* Center align */
    flex-wrap: wrap;
    gap: var(--space-lg);
    font-family: var(--font-ui);
    font-size: 1rem;
    color: var(--color-text-tertiary);
    margin-bottom: var(--space-xl);
    padding-bottom: var(--space-lg);
    border-bottom: 1px solid var(--color-border);
}

.post-featured-image {
    margin: var(--space-3xl) 0;
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.post-featured-image img {
    width: 100%;
}

/* Article Content */
.article-content {
    font-size: 1.25rem;
    line-height: 1.8;
    max-width: var(--reading-max);
    margin: 0 auto; /* Center the reading column */
}

/* Drop Cap for first paragraph */
.article-content > p:first-of-type::first-letter {
    float: left;
    font-size: 5rem;
    line-height: 0.8;
    font-weight: 700;
    margin-right: 1rem;
    margin-top: 0.2rem;
    color: var(--color-accent-dark);
    font-family: var(--font-heading);
}

.article-content h2 {
    font-size: 2rem;
    margin-top: var(--space-xl);
    margin-bottom: var(--space-md);
    color: var(--color-text-primary);
}

.article-content h3 {
    font-size: 1.5rem;
    margin-top: var(--space-lg);
    margin-bottom: var(--space-sm);
}

.article-content h2,
.article-content h3 {
    scroll-margin-top: 100px; /* For TOC links */
}

/* Post Tags */
.post-tags {
    margin-top: var(--space-2xl);
    padding-top: var(--space-xl);
    border-top: 1px solid var(--color-border);
}

.post-tags h3 {
    font-size: 1.125rem;
    margin-bottom: var(--space-sm);
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-sm);
}

.tag {
    padding: var(--space-sm) var(--space-lg);
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    font-family: var(--font-ui);
    font-size: 0.875rem;
    color: var(--color-accent-dark);
    transition: var(--transition-base);
}

.tag:hover {
    background: var(--color-accent-lighter);
}

/* Share Buttons */
.post-share {
    margin-top: var(--space-xl);
    padding-top: var(--space-lg);
    border-top: 1px solid var(--color-border);
}

.post-share h3 {
    font-size: 1.125rem;
    margin-bottom: var(--space-sm);
}

.share-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-md);
}

.share-btn {
    padding: var(--space-sm) var(--space-lg);
    border-radius: var(--radius-md);
    font-family: var(--font-ui);
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition-base);
}

.share-btn--facebook { background: #1877F2; color: white; }
.share-btn--twitter { background: #1DA1F2; color: white; }
.share-btn--linkedin { background: #0A66C2; color: white; }
.share-btn--email { background: var(--color-accent-dark); color: white; }

.share-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Author Bio */
.author-bio {
    margin-top: var(--space-2xl);
    padding: var(--space-xl);
    background: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
}

.author-bio h3 {
    margin-bottom: var(--space-lg);
}

.author-bio h4 {
    color: var(--color-accent-dark);
    margin-bottom: var(--space-sm);
}

/* Table of Contents */
.table-of-contents {
    position: sticky;
    top: 100px;
    background: var(--color-bg-tertiary);
    padding: var(--space-xl);
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border);
}

@media (max-width: 1023px) {
    .table-of-contents {
        display: none;
    }
}

.table-of-contents h3 {
    font-size: 1rem;
    margin-bottom: var(--space-md);
}

.toc-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.toc-item {
    margin-bottom: var(--space-sm);
}

.toc-item--sub {
    margin-left: var(--space-lg);
    font-size: 0.875rem;
}

.toc-link {
    color: var(--color-text-secondary);
    font-family: var(--font-ui);
    font-size: 0.9375rem;
}

.toc-link:hover {
    color: var(--color-accent-dark);
}

/* Related Posts */
.related-posts {
    margin-top: var(--space-6xl);
    padding-top: var(--space-5xl);
    border-top: 1px solid var(--color-border);
}

.related-posts h2 {
    text-align: center;
    margin-bottom: var(--space-3xl);
}

/* Breadcrumb */
.breadcrumb {
    margin-bottom: var(--space-2xl);
}

.breadcrumb__list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-sm);
    list-style: none;
    padding: 0;
    margin: 0;
    font-family: var(--font-ui);
    font-size: 0.875rem;
}

.breadcrumb__item:not(:last-child)::after {
    content: '→';
    margin-left: var(--space-sm);
    color: var(--color-text-tertiary);
}

.breadcrumb__item a {
    color: var(--color-text-secondary);
}

.breadcrumb__item span {
    color: var(--color-text-tertiary);
}
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
