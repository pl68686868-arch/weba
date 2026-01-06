<?php
declare(strict_types=1);

/**
 * Homepage - Trang chủ
 * 
 * Features:
 * - Professional opening statement
 * - 4 Pillars grid
 * - Featured/recent articles
 * - Premium breathing space design
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/Cache.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize SEO
$seo = new SEO();
$seo->setTitle('Trang chủ', false) // Don't append site name for homepage
    ->setDescription('Không gian chia sẻ về tâm lý học, chánh niệm và phát triển con người dành cho người trưởng thành đang tìm kiếm chiều sâu nội tâm, ý nghĩa công việc và sự hồi phục thân-tâm.')
    ->setCanonical(SITE_URL . '/')
    ->setOGType('website')
    ->setOGImage(DEFAULT_OG_IMAGE);

// Initialize Database
$db = Database::getInstance();
$cache = new Cache();

// Get the 4 pillars (categories)
$pillars = $cache->remember('homepage_pillars', function() use ($db) {
    return $db->fetchAll(
        "SELECT id, name, slug, description 
         FROM categories 
         ORDER BY display_order ASC"
    );
}, 3600); // Cache for 1 hour

// Get featured/recent articles
$featuredPosts = $cache->remember('homepage_featured_posts', function() use ($db) {
    return $db->fetchAll(
        "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name
         FROM posts p
         JOIN categories c ON p.category_id = c.id
         JOIN users u ON p.author_id = u.id
         WHERE p.status = 'published'
         ORDER BY p.is_featured DESC, p.published_at DESC
         LIMIT 6",
        []
    );
}, 300); // Cache for 5 minutes

// Track page view
trackPageView(null, '/');

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero__content">
            <p class="hero__text">
                Tôi là giảng viên và người thực hành tâm lý, thực hành và giảng dạy dựa trên nền tảng chánh niệm. 
                Công việc của tôi gắn liền với việc quan sát, lắng nghe và đồng hành cùng đời sống nội tâm của con người 
                trong bối cảnh học tập, làm việc và trưởng thành.
            </p>
            <p class="hero__text">
                Website này là không gian tôi viết và chia sẻ những suy tư, kiến thức và trải nghiệm thực hành 
                dành cho người trưởng thành đang ở trong hành trình tìm kiếm chiều sâu nội tâm, ý nghĩa trong công việc 
                và sự hồi phục thân–tâm. Các nội dung được tiếp cận từ góc nhìn tâm lý học ứng dụng, chánh niệm 
                và phản tư nghề nghiệp, với mong muốn góp phần nuôi dưỡng một đời sống tỉnh thức, bền vững và có ý nghĩa hơn.
            </p>
        </div>
    </div>
</section>

<!-- The 4 Pillars -->
<section class="pillars">
    <div class="container">
        <h2 class="text-center">Bốn trụ cột</h2>
        
        <div class="pillars__grid">
            <?php foreach ($pillars as $pillar): ?>
                <article class="pillar-card">
                    <h3 class="pillar-card__title">
                        <?= htmlspecialchars($pillar['name']) ?>
                    </h3>
                    <p class="pillar-card__description">
                        <?= htmlspecialchars($pillar['description']) ?>
                    </p>
                    <a href="/category/<?= htmlspecialchars($pillar['slug']) ?>" class="pillar-card__link">
                        Khám phá →
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured / Recent Articles -->
<?php if (!empty($featuredPosts)): ?>
<section class="featured-articles">
    <div class="container">
        <h2 class="text-center">Bài viết mới & nổi bật</h2>
        
        <div class="articles-grid">
            <?php foreach ($featuredPosts as $post): ?>
                <article class="article-card">
                    <?php if ($post['featured_image']): ?>
                        <img 
                            src="<?= UPLOAD_URL . '/' . htmlspecialchars($post['featured_image']) ?>" 
                            alt="<?= htmlspecialchars($post['title']) ?>"
                            class="article-card__image"
                            loading="lazy"
                        >
                    <?php endif; ?>
                    
                    <div class="article-card__content">
                        <a href="/category/<?= htmlspecialchars($post['category_slug']) ?>" class="article-card__category">
                            <?= htmlspecialchars($post['category_name']) ?>
                        </a>
                        
                        <h3 class="article-card__title">
                            <a href="/post/<?= htmlspecialchars($post['slug']) ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h3>
                        
                        <?php if ($post['excerpt']): ?>
                            <p class="article-card__excerpt">
                                <?= htmlspecialchars(generateExcerpt($post['excerpt'], 120)) ?>
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
        
        <div class="text-center mt-3xl">
            <a href="/writing.php" class="btn btn-secondary">
                Xem tất cả bài viết →
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
