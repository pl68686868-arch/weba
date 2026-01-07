<?php declare(strict_types=1);

/**
 * About Page - Giới thiệu
 * 
 * Personal journey, values, and professional philosophy
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize SEO
$seo = new SEO();
$seo->setTitle('Giới thiệu')
    ->setDescription('Con đường nghề nghiệp gắn liền với giáo dục, đào tạo và thực hành tâm lý học ứng dụng, đặc biệt trong bối cảnh người trưởng thành học tập, làm việc và đối diện với những chuyển động nội tâm.')
    ->setCanonical(SITE_URL . '/about.php')
    ->setOGType('profile')
    ->setOGImage(DEFAULT_OG_IMAGE)
    ->generatePersonSchema([
        'name' => SITE_NAME,
        'job_title' => SITE_TAGLINE,
        'description' => 'Giảng viên và người thực hành tâm lý, thực hành và giảng dạy dựa trên nền tảng chánh niệm.',
        'email' => FROM_EMAIL
    ]);

// Track page view
trackPageView(null, '/about.php');

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Section 1: Hero / Manifesto -->
<section class="about-hero">
    <div class="container">
        <div class="about-hero__grid">
            <div class="about-hero__content">
                <span class="eyebrow">Người bạn đồng hành</span>
                <h1 class="about-hero__title">
                    Chánh niệm,<br>
                    Tâm lý học &<br>
                    <span class="text-accent">Sự trưởng thành</span>
                </h1>
                <div class="about-hero__desc">
                    <p>
                        Tôi tin rằng việc học cách dừng lại, quan sát và hiểu mình một cách tỉnh thức là nền tảng 
                        quan trọng để mỗi người sống và làm việc có ý nghĩa hơn, không chỉ cho hiện tại mà cả về lâu dài.
                    </p>
                    <p>
                        Tôi là Danny, một người thực hành và giảng dạy tâm lý học ứng dụng, dành sự quan tâm đặc biệt 
                        cho đời sống nội tâm của người trưởng thành.
                    </p>
                </div>
            </div>
            <div class="about-hero__visual">
                <div class="about-hero__image-wrapper">
                    <?php 
                    $heroImage = get_setting('about_hero_image');
                    if ($heroImage): 
                    ?>
                        <img src="<?= UPLOAD_URL . '/' . escape($heroImage) ?>" alt="Danny Duong - Chân dung" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <!-- Placeholder -->
                        <div class="placeholder-portrait" style="background: #E5E5E5; width: 100%; height: 600px; display: flex; align-items: center; justify-content: center; color: #999;">
                            [Ảnh chân dung - 4:5]
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Philosophy (Dark Break) -->
<section class="about-philosophy">
    <div class="container container--narrow">
        <div class="philosophy-content">
            <h2 class="section-title text-light">Triết lý thực hành</h2>
            <blockquote class="philosophy-quote">
                "Không giản lược. Không thần thánh hóa.<br>
                Chỉ đơn giản là <span class="text-gold">hiểu sâu sắc</span>."
            </blockquote>
            <p class="text-light-soft">
                Trong công việc giảng dạy và thực hành, tôi quan tâm đến cách con người trải nghiệm áp lực, 
                ý nghĩa công việc, sự mệt mỏi tinh thần cũng như nhu cầu hồi phục thân–tâm trong đời sống hiện đại. 
                Tôi tiếp cận các vấn đề này từ sự kết hợp giữa tâm lý học, chánh niệm và phản tư nghề nghiệp.
            </p>
        </div>
    </div>
</section>

<!-- Section 3: The 4 Pillars (Grid) -->
<section class="about-pillars">
    <div class="container">
        <div class="section-header text-center">
            <span class="eyebrow">Nội dung cốt lõi</span>
            <h2 class="section-title">Bốn trụ cột</h2>
        </div>
        
        <div class="pillars-grid">
            <!-- Pillar 1 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">01</span>
                <h3>Tâm lý học &<br>Đời sống</h3>
                <p>Hiểu về lo âu, cô đơn và những khủng hoảng của người trưởng thành dưới lăng kính khoa học.</p>
            </div>
            
            <!-- Pillar 2 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">02</span>
                <h3>Chánh niệm &<br>Hồi phục</h3>
                <p>Những thực hành nhỏ, không mang màu sắc tôn giáo, giúp tái tạo năng lượng cho thân và tâm.</p>
            </div>
            
            <!-- Pillar 3 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">03</span>
                <h3>Giáo dục &<br>Học tập</h3>
                <p>Học tập suốt đời (Lifelong Learning) và vai trò của sự phản tư trong quá trình phát triển.</p>
            </div>
            
            <!-- Pillar 4 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">04</span>
                <h3>Phản tư<br>Nghề nghiệp</h3>
                <p>Những ghi chép cá nhân về hành trình làm nghề, giảng dạy và tư vấn tâm lý.</p>
            </div>
        </div>
        
        <div class="about-cta text-center">
            <a href="/contact.php" class="btn btn-primary">Cùng kết nối</a>
        </div>
    </div>
</section>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
