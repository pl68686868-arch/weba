<?php
declare(strict_types=1);

/**
 * About Page - Giới thiệu
 * 
 * Personal journey, values, and professional philosophy
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
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

<article class="about-page">
    <div class="container">
        <div class="content-width">
            <header class="page-header">
                <h1>Giới thiệu</h1>
            </header>
            
            <div class="about-content">
                <section class="about-section">
                    <p>
                        Tôi là giảng viên và người thực hành tâm lý, thực hành và giảng dạy dựa trên nền tảng chánh niệm. 
                        Con đường nghề nghiệp của tôi gắn liền với giáo dục, đào tạo và thực hành tâm lý học ứng dụng, 
                        đặc biệt trong bối cảnh người trưởng thành học tập, làm việc và đối diện với những chuyển động nội tâm 
                        của chính mình.
                    </p>
                </section>
                
                <section class="about-section">
                    <h2>Con đường & Giá trị</h2>
                    <p>
                        Trong công việc giảng dạy và thực hành, tôi quan tâm đến cách con người trải nghiệm áp lực, 
                        ý nghĩa công việc, sự mệt mỏi tinh thần cũng như nhu cầu hồi phục thân–tâm trong đời sống hiện đại. 
                        Tôi tiếp cận các vấn đề này từ sự kết hợp giữa tâm lý học, chánh niệm và phản tư nghề nghiệp, 
                        với tinh thần thận trọng, không giản lược và không thần thánh hóa thực hành.
                    </p>
                </section>
                
                <section class="about-section">
                    <h2>Vì sao tôi viết – dạy – thực hành</h2>
                    <p>
                        Website này là không gian tôi viết và chia sẻ những suy tư, kiến thức và trải nghiệm tích lũy 
                        trong quá trình làm nghề. Các bài viết hướng đến người trưởng thành đang ở trong hành trình 
                        tìm kiếm chiều sâu nội tâm, ý nghĩa trong công việc và sự cân bằng bền vững trong đời sống.
                    </p>
                    
                    <p>
                        Tôi tin rằng việc học cách dừng lại, quan sát và hiểu mình một cách tỉnh thức là nền tảng quan trọng 
                        để mỗi người sống và làm việc có ý nghĩa hơn, không chỉ cho hiện tại mà cả về lâu dài.
                    </p>
                </section>
                
                <section class="about-section">
                    <h2>Bốn trụ cột nội dung</h2>
                    <p>
                        Các bài viết trên website này được tổ chức theo bốn trụ cột chính, phản ánh những lĩnh vực 
                        tôi quan tâm và làm việc:
                    </p>
                    
                    <ul class="pillars-list">
                        <li>
                            <strong>Tâm lý học & Đời sống trưởng thành</strong> — 
                            Viết về lo âu, cô đơn, khủng hoảng tuổi trung niên, động lực, ý nghĩa sống 
                            và sự trưởng thành tâm lý.
                        </li>
                        <li>
                            <strong>Chánh niệm & Hồi phục thân–tâm</strong> — 
                            Chánh niệm đúng nghĩa, không thần thánh hóa. Thực hành nhỏ để hồi phục nội tâm.
                        </li>
                        <li>
                            <strong>Giáo dục & Học tập người trưởng thành</strong> — 
                            Vai trò của phản tư, trải nghiệm và chánh niệm trong học tập suốt đời.
                        </li>
                        <li>
                            <strong>Phản tư nghề nghiệp</strong> — 
                            Những suy tư cá nhân trong hành trình làm nghề giảng dạy và thực hành tâm lý.
                        </li>
                    </ul>
                </section>
                
                <section class="about-section">
                    <h2>Liên hệ</h2>
                    <p>
                        Nếu bạn muốn trao đổi về giảng dạy, đào tạo, hợp tác học thuật hoặc những kết nối chuyên môn 
                        cùng quan tâm đến chiều sâu nội tâm và phát triển bền vững, vui lòng liên hệ qua 
                        <a href="/contact.php">trang liên hệ</a> hoặc email: 
                        <a href="mailto:<?= htmlspecialchars(FROM_EMAIL) ?>"><?= htmlspecialchars(FROM_EMAIL) ?></a>
                    </p>
                </section>
            </div>
        </div>
    </div>
</article>

<?php
// Add some custom styles for About page
?>
<style>
.about-page {
    padding: var(--space-4xl) 0;
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-5xl);
}

.page-header h1 {
    margin-bottom: var(--space-md);
}

.about-content {
    max-width: var(--content-max);
    margin: 0 auto;
}

.about-section {
    margin-bottom: var(--space-5xl);
}

.about-section:last-child {
    margin-bottom: 0;
}

.pillars-list {
    list-style: none;
    padding: 0;
    margin-top: var(--space-xl);
}

.pillars-list li {
    padding: var(--space-lg);
    background: var(--color-bg-tertiary);
    border-left: 4px solid var(--color-accent-medium);
    margin-bottom: var(--space-lg);
    border-radius: var(--radius-md);
}

.pillars-list li:last-child {
    margin-bottom: 0;
}

.pillars-list strong {
    color: var(--color-accent-dark);
    display: block;
    margin-bottom: var(--space-sm);
}
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
