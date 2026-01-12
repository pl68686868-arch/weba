<?php declare(strict_types=1);

/**
 * Podcast / Projects Page - Podcast & Dự án
 * 
 * Showcase of podcast episodes and creative projects
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// SEO Setup
$seo = new SEO();
$seo->setTitle('Podcast / Dự án')
    ->setDescription('Không gian chia sẻ và đối thoại chậm rãi về đời sống nội tâm, công việc và quá trình trưởng thành của người lớn, tiếp cận từ góc nhìn chánh niệm và tâm lý học.')
    ->setCanonical(SITE_URL . '/podcast.php')
    ->setOGType('website')
    ->setOGImage(DEFAULT_OG_IMAGE);

// Track page view
trackPageView(null, '/podcast.php');

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Section 1: Hero -->
<section class="about-hero" style="padding-bottom: 2rem;"> <!-- Shared Hero Style -->
    <div class="container">
        <div class="about-hero__grid">
            <div class="about-hero__content">
                <span class="eyebrow">The Podcast</span>
                <h1 class="about-hero__title">
                    Đối thoại<br>
                    Nội tâm &<br>
                    <span class="text-accent">Chánh niệm</span>
                </h1>
                <div class="about-hero__desc">
                    <p>
                        Không gian cho những cuộc trò chuyện chậm rãi về những điều thường bị bỏ quên trong sự hối hả 
                        của đời sống thường nhật. Nơi chúng ta cùng ngồi lại, lắng nghe và hiểu sâu hơn về chính mình.
                    </p>
                </div>
                <div class="hero-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button class="btn btn-primary">Nghe trailer</button>
                    <a href="#subscribe" class="btn btn-outline">Đăng ký</a>
                </div>
            </div>
            <div class="about-hero__visual">
                <div class="about-hero__image-wrapper">
                    <?php 
                    $coverArt = get_setting('podcast_cover_art');
                    if ($coverArt): 
                    ?>
                        <img src="<?= UPLOAD_URL . '/' . escape($coverArt) ?>" alt="Podcast Cover Art" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <!-- Placeholder -->
                        <div class="placeholder-portrait" style="background: #1E2522; width: 100%; height: 600px; display: flex; align-items: center; justify-content: center; color: #fff;">
                            [Podcast Cover Art]
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Themes Grid (Album Style) -->
<!-- Section 2: Podcast Episodes -->
<section class="podcast-library" style="padding: 1rem 0;">
    <div class="container">
        <div class="section-header text-center">
            <span class="eyebrow">Tập mới nhất</span>
            <h2 class="section-title">Danh sách phát</h2>
        </div>
        
        <?php
        // Get current category filter
        $categorySlug = $_GET['category'] ?? '';
        
        // Build query
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM posts p 
                JOIN categories c ON p.category_id = c.id
                WHERE p.post_type = 'podcast' AND p.status = 'published'";
        $params = [];
        
        if (!empty($categorySlug)) {
            $sql .= " AND c.slug = :slug";
            $params['slug'] = $categorySlug;
        }
        
        $sql .= " ORDER BY p.published_at DESC";
        
        $podcasts = $db->fetchAll($sql, $params);
        
        // Fetch categories that have podcasts
        $categories = $db->fetchAll(
            "SELECT DISTINCT c.* FROM categories c 
             JOIN posts p ON c.id = p.category_id 
             WHERE p.post_type = 'podcast' AND p.status = 'published' 
             ORDER BY c.name"
        );
        ?>
        
        <!-- Category Filter -->
        <?php if (!empty($categories)): ?>
            <div class="podcast-filter" style="display: flex; justify-content: center; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                <a href="/podcast.php" class="btn btn-outline <?= empty($categorySlug) ? 'active' : '' ?>" style="border-radius: 20px;">Tất cả</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="/podcast.php?category=<?= escape($cat['slug']) ?>" 
                       class="btn btn-outline <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>"
                       style="border-radius: 20px;">
                        <?= escape($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($podcasts)): ?>
            <div class="podcast-grid">
                <?php foreach ($podcasts as $podcast): ?>
                    <a href="/post/<?= escape($podcast['slug']) ?>" class="podcast-card-link">
                        <div class="podcast-card">
                            <div class="podcast-card__cover" style="<?= $podcast['featured_image'] ? "background-image: url('" . UPLOAD_URL . '/' . escape($podcast['featured_image']) . "'); background-size: cover; background-position: center;" : "background-color: #e9ecef;" ?>">
                                <?php if (!$podcast['featured_image']): ?>
                                    <span class="podcast-card__icon">🎙️</span>
                                <?php endif; ?>
                            </div>
                            <div class="podcast-card__content">
                                <h3 style="margin-bottom: 0.5rem; font-size: 1.25rem;"><?= escape($podcast['title']) ?></h3>
                                <p style="font-size: 0.9rem; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= escape($podcast['excerpt'] ?? '') ?>
                                </p>
                                <span style="font-size: 0.8rem; color: #999; margin-top: auto; display: block; padding-top: 10px;">
                                    <?= formatDate($podcast['published_at'], 'short') ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 40px;">
                <p>Chưa có tập podcast nào.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .podcast-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .podcast-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #eee;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        background: white;
    }
    .podcast-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .podcast-card__cover {
        aspect-ratio: 1/1;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .podcast-card__icon {
        font-size: 3rem;
    }
    .podcast-card__content {
        padding: 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .podcast-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        margin-top: 40px;
    }
    
    .btn.active {
        background-color: var(--color-accent-dark, #2C5F4F);
        color: white;
        border-color: var(--color-accent-dark, #2C5F4F);
    }
</style>

<!-- Section 3: Subscription -->
<section id="subscribe" class="podcast-subscribe section-spacing bg-tertiary">
    <div class="container container--narrow text-center">
        <h2 class="section-title">Đăng ký theo dõi</h2>
        <p class="section-desc">
            Podcast có mặt trên tất cả các nền tảng phổ biến. Đăng ký ngay để không bỏ lỡ tập mới nhất.
        </p>
        
        <div class="platform-links">
            <a href="#" class="platform-btn spotify">
                <span>Spotify</span>
            </a>
            <a href="#" class="platform-btn apple">
                <span>Apple Podcast</span>
            </a>
            <a href="/rss.php" class="platform-btn rss">
                <span>RSS Feed</span>
            </a>
        </div>
    </div>
</section>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
