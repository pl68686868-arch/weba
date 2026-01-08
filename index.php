<?php declare(strict_types=1);

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

<!-- Hero Section - Magazine Split Layout -->
<section class="hero">
    <div class="container hero__container">
        <!-- Text Content (Left) -->
        <div class="hero__content">
            <h1 class="hero__title">
                Dương Trần Minh Đoàn
                <span class="hero__subtitle">Giảng viên, người thực hành tâm lý và chánh niệm</span>
            </h1>
            <p class="hero__text">
                Tôi là giảng viên và người thực hành tâm lý, thực hành và giảng dạy dựa trên nền tảng chánh niệm. 
                Công việc của tôi gắn liền với việc quan sát, lắng nghe và đồng hành cùng đời sống nội tâm.
            </p>
        </div>
        
        <!-- Hero Visual (Right) - Auto-fading Gallery -->
        <div class="hero__visual">
            <div class="hero__gallery">
                <!-- Image 1 -->
                <img src="uploads/portrait-1.jpg" 
                     alt="Portrait 1" 
                     class="hero__img active">
                <!-- Image 2 -->
                <img src="uploads/portrait-2.jpg" 
                     alt="Portrait 2" 
                     class="hero__img">
                <!-- Image 3 -->
                <img src="uploads/portrait-3.jpg" 
                     alt="Portrait 3" 
                     class="hero__img">
                
                <!-- Fallback to placeholder if uploads not found (JS will handle logic or CSS fallback) -->
                <img src="https://images.unsplash.com/photo-1515023115689-589c33041697?q=80&w=2400" 
                     alt="Fallback" 
                     class="hero__img fallback" style="display:none;">
            </div>
        </div>
    </div>
</section>

<!-- Introduction - Fade-In Experience -->
<section class="intro">
    <div class="intro__parallax-bg" style="background-image: url('https://images.unsplash.com/photo-1499244571948-7ccddb30d331?q=80&w=2800&auto=format&fit=crop');"></div>
    <div class="intro__overlay"></div>
    <div class="container intro__container">
        <div class="intro__text-wrapper">
            <p class="intro__text">
                Website này là không gian tôi viết và chia sẻ những suy tư, kiến thức và trải nghiệm thực hành 
                dành cho người trưởng thành đang ở trong hành trình tìm kiếm chiều sâu nội tâm, ý nghĩa trong công việc 
                và sự hồi phục thân–tâm.
            </p>
            <p class="intro__text">
                Các nội dung được tiếp cận từ góc nhìn tâm lý học ứng dụng, chánh niệm và phản tư nghề nghiệp, 
                với mong muốn góp phần nuôi dưỡng một đời sống tỉnh thức, bền vững và có ý nghĩa hơn.
            </p>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials section-spacing">
    <div class="container container--narrow">
        <div class="section-header text-center">
            <span class="eyebrow" style="color: var(--color-gold);">Chia sẻ</span>
            <h2 class="section-title">Từ những người đã đồng hành</h2>
        </div>
        
        <div class="testimonials-grid" style="display: grid; gap: 2rem; margin-top: 3rem;">
            <blockquote class="testimonial-card" style="background: white; padding: 2.5rem; border-radius: 12px; border-left: 4px solid var(--color-accent-medium); box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <p style="font-style: italic; font-size: 1.125rem; line-height: 1.8; color: var(--color-text-secondary); margin-bottom: 1.5rem;">
                    "Thầy Đoàn đã giúp tôi nhìn nhận lại bản thân một cách nhẹ nhàng và sâu sắc. 
                    Những buổi hướng dẫn chánh niệm thực sự mang lại sự bình an mà tôi đã tìm kiếm rất lâu."
                </p>
                <cite style="font-family: var(--font-ui); font-size: 0.875rem; color: var(--color-text-tertiary); font-style: normal;">
                    — Học viên khóa Mindfulness At Work
                </cite>
            </blockquote>
            
            <blockquote class="testimonial-card" style="background: white; padding: 2.5rem; border-radius: 12px; border-left: 4px solid var(--color-gold); box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <p style="font-style: italic; font-size: 1.125rem; line-height: 1.8; color: var(--color-text-secondary); margin-bottom: 1.5rem;">
                    "Cách tiếp cận của thầy rất khoa học nhưng đồng thời cũng rất gần gũi. 
                    Tôi học được cách lắng nghe bản thân và không còn sợ đối diện với những cảm xúc khó khăn."
                </p>
                <cite style="font-family: var(--font-ui); font-size: 0.875rem; color: var(--color-text-tertiary); font-style: normal;">
                    — Học viên khóa Tâm lý học Ứng dụng
                </cite>
            </blockquote>
        </div>
    </div>
</section>

<!-- The 4 Pillars - Visual Posters Grid -->
<section class="pillars">
    <div class="container">
        <!-- No Heading -> Pure Visuals -->
        <div class="pillars__grid">
            <?php 
            // Define premium generated images for pillars
            $pillarImages = [
                'tam-ly-hoc-doi-song-truong-thanh' => 'uploads/pillar-psychology.png',
                'chanh-niem-hoi-phuc-than-tam' => 'uploads/pillar-mindfulness.png',
                'giao-duc-hoc-tap-nguoi-truong-thanh' => 'uploads/pillar-education.png',
                'phan-tu-nghe-nghiep' => 'uploads/pillar-career.png'
            ];
            ?>
            
            <?php foreach ($pillars as $pillar): ?>
                <?php $bgImage = $pillarImages[$pillar['slug']] ?? 'https://images.unsplash.com/photo-1508672019048-805c2763d46d?q=80&w=1000'; ?>
                
                <a href="/category/<?= htmlspecialchars($pillar['slug']) ?>" class="pillar-card pillar-card--poster">
                    <div class="pillar-card__bg" style="background-image: url('<?= $bgImage ?>');"></div>
                    <div class="pillar-card__overlay"></div>
                    
                    <div class="pillar-card__content">
                        <h3 class="pillar-card__title">
                            <?= htmlspecialchars($pillar['name']) ?>
                        </h3>
                        <!-- Description added back for Hover Reveal -->
                        <p class="pillar-card__reveal-text">
                            <?= htmlspecialchars($pillar['description']) ?>
                        </p>
                        <div class="pillar-card__action">
                            <span class="btn-text">Khám phá</span>
                            <span class="btn-icon">→</span>
                        </div>
                    </div>
                </a>
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
                            <a href="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>">
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
