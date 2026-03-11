<?php declare(strict_types=1);

/**
 * Teaching & Training Page - Giảng dạy & Đào tạo
 * 
 * Showcase of teaching activities and training programs
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
$seo->setTitle('Giảng dạy & Đào tạo')
    ->setDescription('Các hoạt động giảng dạy và đào tạo dựa trên tâm lý học ứng dụng và chánh niệm, hướng đến việc học sâu, tự điều chỉnh và làm việc có ý nghĩa của người trưởng thành.')
    ->setCanonical(SITE_URL . '/teaching.php')
    ->setOGType('website')
    ->setOGImage(DEFAULT_OG_IMAGE);

// Track page view
trackPageView(null, '/teaching.php');

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Section 1: Hero (Refined) -->
<section class="teaching-hero section-spacing">
    <div class="container">
        <div class="about-hero__grid">
            <div class="about-hero__content">
                <span class="eyebrow">Giảng dạy & Đào tạo</span>
                <h1 class="about-hero__title">
                    Khai phóng<br>
                    Tiềm năng<br>
                    <span class="text-accent" style="color: var(--color-accent-medium);">Con người</span>
                </h1>
                <div class="about-hero__desc">
                    <p>
                        <?= escape(get_setting('teaching_hero_desc', 'Tôi tin rằng giáo dục không chỉ là truyền tải kiến thức, mà là quá trình khơi gợi sự chuyển hóa từ bên trong. Hành trình học tập của người trưởng thành cần sự kết hợp giữa hiểu biết khoa học và trải nghiệm thực chứng.')) ?>
                    </p>
                </div>
                <div class="hero-actions" style="margin-top: 2rem;">
                    <a href="#areas" class="btn btn-primary" style="background: var(--color-text-primary); color: white; padding: 1rem 2rem; border-radius: 50px; text-decoration: none; display: inline-block; transition: all 0.3s ease;">
                        Khám phá lĩnh vực
                    </a>
                </div>
            </div>
            
            <div class="teaching-hero__visual">
                <?php 
                $heroImage = get_setting('teaching_hero_image');
                if ($heroImage): 
                ?>
                    <img src="<?= UPLOAD_URL . '/' . escape($heroImage) ?>" alt="Giảng dạy & Đào tạo">
                <?php else: ?>
                    <!-- Minimalist Placeholder -->
                    <div style="background: #EBE8E0; width: 100%; aspect-ratio: 4/5; display: flex; align-items: center; justify-content: center; color: var(--color-text-tertiary); font-family: var(--font-ui);">
                        [Teaching Image Placeholder]
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Methodology (Clean Grid) -->
<section class="teaching-methodology section-spacing">
    <div class="container container--narrow">
        <div class="text-center">
            <h2 class="section-title">Phương pháp tiếp cận</h2>
            <p class="section-desc" style="max-width: 600px; margin: 1.5rem auto 0;">
                Các chương trình được thiết kế dựa trên nền tảng <strong>Tâm lý học ứng dụng</strong> kết hợp với 
                <strong>Chánh niệm (Mindfulness)</strong>, hướng đến ba mục tiêu cốt lõi:
            </p>
        </div>
        
        <div class="method-grid">
            <div class="method-card">
                <span class="method-icon">🧠</span>
                <h3>Hiểu mình</h3>
                <p>Nhận diện cảm xúc, mô thức tư duy và động lực bên trong thông qua kiến thức tâm lý học.</p>
            </div>
            <div class="method-card">
                <span class="method-icon">🧘</span>
                <h3>An trú</h3>
                <p>Khả năng quay về thực tại, giảm căng thẳng và tái tạo năng lượng nhờ thực hành chánh niệm.</p>
            </div>
            <div class="method-card">
                <span class="method-icon">🌱</span>
                <h3>Chuyển hóa</h3>
                <p>Ứng dụng bài học vào công việc và đời sống để tạo ra những thay đổi bền vững.</p>
            </div>
        </div>
    </div>
</section>

<!-- Section 3: Areas Grid (Polished) -->
<section id="areas" class="teaching-areas-section section-spacing" style="background: var(--color-bg-secondary);">
    <div class="container">
        <div class="section-header text-center" style="margin-bottom: 4rem;">
            <span class="eyebrow" style="color: var(--color-gold); font-family: var(--font-ui); text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.875rem;">Chuyên môn</span>
            <h2 class="section-title" style="margin-top: 0.5rem;">Lĩnh vực giảng dạy</h2>
        </div>
        
        <div class="pillars-grid">
            <!-- Area 1 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">01</span>
                <h3>Tâm lý học<br>Ứng dụng</h3>
                <p>Các môn học nền tảng về tâm lý phát triển, tâm lý học xã hội và hành vi con người trong tổ chức.</p>
            </div>
            
            <!-- Area 2 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">02</span>
                <h3>Mindfulness<br>At Work</h3>
                <p>Mang chánh niệm vào môi trường công sở: Giảm burnout, tăng tập trung và trí tuệ cảm xúc (EQ).</p>
            </div>
            
            <!-- Area 3 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">03</span>
                <h3>Adult<br>Education</h3>
                <p>Phương pháp sư phạm cho người trưởng thành (Andragogy) và học tập qua trải nghiệm.</p>
            </div>
            
            <!-- Area 4 -->
            <div class="pillar-card-minimal">
                <span class="pillar-num">04</span>
                <h3>Career<br>Development</h3>
                <p>Mentoring và Coaching định hướng phát triển nghề nghiệp cho giảng viên và chuyên gia L&D.</p>
            </div>
        </div>
    </div>
</section>

<!-- Section 4: CTA (Minimal) -->
<section class="teaching-cta section-spacing text-center" style="padding-bottom: 8rem;">
    <div class="container container--narrow">
        <h2 class="section-title"><?= escape(get_setting('teaching_cta_title', 'Hợp tác Đào tạo')) ?></h2>
        <p class="section-desc" style="margin-bottom: 2rem;">
            <?= escape(get_setting('teaching_cta_desc', 'Tôi luôn sẵn sàng cho các cơ hội hợp tác giảng dạy tại trường Đại học, Doanh nghiệp hoặc các dự án cộng đồng.')) ?>
        </p>
        <a href="/contact.php?purpose=teaching" class="btn btn-outline" style="border: 1px solid var(--color-text-primary); color: var(--color-text-primary); padding: 1rem 2.5rem; text-decoration: none; border-radius: 50px; font-weight: 500; transition: all 0.3s ease; display: inline-block;">
            Cùng trò chuyện
        </a>
    </div>
</section>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
