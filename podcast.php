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
<section class="about-hero"> <!-- Shared Hero Style -->
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
<section class="podcast-library section-spacing">
    <div class="container">
        <div class="section-header text-center">
            <span class="eyebrow">Chủ đề chính</span>
            <h2 class="section-title">Thư viện nội dung</h2>
        </div>
        
        <div class="podcast-grid">
            <!-- Theme 1 -->
            <div class="podcast-card">
                <div class="podcast-card__cover" style="background: #D4AF75;">
                    <span class="podcast-card__icon">🧠</span>
                </div>
                <div class="podcast-card__content">
                    <h3>Tâm lý học thường thức</h3>
                    <p>Giải mã những cảm xúc phức tạp: lo âu, ghen tị, cô đơn và sự tìm kiếm ý nghĩa.</p>
                </div>
            </div>
            
            <!-- Theme 2 -->
            <div class="podcast-card">
                <div class="podcast-card__cover" style="background: #8A9A95;">
                    <span class="podcast-card__icon">🌿</span>
                </div>
                <div class="podcast-card__content">
                    <h3>Thực hành Chánh niệm</h3>
                    <p>Những bài tập nhỏ giúp bạn neo mình vào hiện tại giữa những xáo trộn.</p>
                </div>
            </div>
            
            <!-- Theme 3 -->
            <div class="podcast-card">
                <div class="podcast-card__cover" style="background: #24332D;">
                    <span class="podcast-card__icon">💼</span>
                </div>
                <div class="podcast-card__content">
                    <h3>Công việc & Sự nghiệp</h3>
                    <p>Làm sao để tìm thấy niềm vui và ý nghĩa trong công việc mỗi ngày?</p>
                </div>
            </div>
            
            <!-- Theme 4 -->
            <div class="podcast-card">
                <div class="podcast-card__cover" style="background: #C4C4C4;">
                    <span class="podcast-card__icon">☕</span>
                </div>
                <div class="podcast-card__content">
                    <h3>Trò chuyện cuối tuần</h3>
                    <p>Những tản mạn vụn vặt nhưng sâu sắc về sách, phim và lối sống.</p>
                </div>
            </div>
        </div>
    </div>
</section>

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
