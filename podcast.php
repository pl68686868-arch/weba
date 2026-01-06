<?php
declare(strict_types=1);

/**
 * Podcast / Projects Page - Podcast & Dự án
 * 
 * Showcase of podcast episodes and creative projects
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
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

<div class="podcast-page">
    <div class="container">
        <div class="content-width">
            <header class="page-header">
                <h1>Podcast / Dự án</h1>
                <p class="page-intro">
                    Không gian chia sẻ và đối thoại chậm rãi về đời sống nội tâm, công việc và quá trình trưởng thành 
                    của người lớn. Nội dung podcast được xây dựng theo tinh thần chánh niệm, chậm rãi và phản tư, 
                    nhằm mở ra những khoảng dừng cần thiết để người nghe lắng lại và quan sát trải nghiệm của chính mình.
                </p>
            </header>
            
            <div class="podcast-content">
                <section class="podcast-section">
                    <h2>Về Podcast</h2>
                    <p>
                        Các tập podcast không hướng đến việc đưa ra lời khuyên nhanh, mà tập trung gợi mở câu hỏi, 
                        chia sẻ góc nhìn và nuôi dưỡng sự hiểu biết sâu sắc hơn về tâm lý học ứng dụng, chánh niệm 
                        và đời sống làm việc có ý nghĩa.
                    </p>
                </section>
                
                <section class="podcast-section">
                    <h2>Chủ đề chính</h2>
                    <div class="podcast-themes">
                        <div class="podcast-theme">
                            <h3>🎧 Đối thoại nội tâm</h3>
                            <p>
                                Khám phá những chuyển động tâm lý trong đời sống hàng ngày, từ lo âu, mệt mỏi 
                                đến sự tìm kiếm ý nghĩa.
                            </p>
                        </div>
                        
                        <div class="podcast-theme">
                            <h3>🧘 Thực hành chánh niệm</h3>
                            <p>
                                Chia sẻ các thực hành chánh niệm đơn giản, hỗ trợ hồi phục thân-tâm và phát triển 
                                sự tỉnh thức trong công việc và đời sống.
                            </p>
                        </div>
                        
                        <div class="podcast-theme">
                            <h3>💼 Làm việc có ý nghĩa</h3>
                            <p>
                                Phản tư về công việc, nghề nghiệp và cách xây dựng một sự nghiệp bền vững, 
                                phù hợp với giá trị cá nhân.
                            </p>
                        </div>
                        
                        <div class="podcast-theme">
                            <h3>📚 Học tập suốt đời</h3>
                            <p>
                                Khám phá cách học và phát triển bản thân ở tuổi trưởng thành, vai trò của trải nghiệm 
                                và phản tư trong quá trình trưởng thành.
                            </p>
                        </div>
                    </div>
                </section>
                
                <section class="podcast-section">
                    <h2>Dự án khác</h2>
                    <p>
                        Ngoài podcast, tôi cũng phát triển các dự án chia sẻ kiến thức và trải nghiệm khác:
                    </p>
                    <ul>
                        <li>Viết sách về tâm lý học ứng dụng và chánh niệm</li>
                        <li>Phát triển khóa học trực tuyến về mindfulness và wellbeing</li>
                        <li>Tổ chức retreat và workshop về phát triển nội tâm</li>
                        <li>Xây dựng cộng đồng học tập và thực hành chánh niệm</li>
                    </ul>
                </section>
                
                <section class="podcast-section">
                    <h2>Theo dõi & Liên hệ</h2>
                    <p>
                        Podcast sẽ sớm ra mắt. Nếu bạn muốn nhận thông báo khi có tập mới hoặc quan tâm đến việc 
                        hợp tác, vui lòng đăng ký newsletter ở cuối trang hoặc liên hệ qua 
                        <a href="/contact.php">trang liên hệ</a>.
                    </p>
                    
                    <div class="podcast-links">
                        <a href="/rss.php" class="podcast-link">
                            <span>📡</span> RSS Feed
                        </a>
                        <!-- Future podcast platforms -->
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.podcast-page {
    padding: var(--space-4xl) 0;
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-5xl);
}

.podcast-content {
    max-width: var(--content-max);
    margin: 0 auto;
}

.podcast-section {
    margin-bottom: var(--space-5xl);
}

.podcast-section:last-child {
    margin-bottom: 0;
}

.podcast-section ul {
    list-style-position: outside;
    padding-left: var(--space-xl);
}

.podcast-section li {
    margin-bottom: var(--space-md);
}

.podcast-themes {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-2xl);
    margin-top: var(--space-xl);
}

@media (min-width: 768px) {
    .podcast-themes {
        grid-template-columns: repeat(2, 1fr);
    }
}

.podcast-theme {
    padding: var(--space-xl);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border);
}

.podcast-theme h3 {
    color: var(--color-accent-dark);
    font-size: 1.25rem;
    margin-bottom: var(--space-md);
}

.podcast-theme p {
    margin: 0;
    color: var(--color-text-secondary);
}

.podcast-links {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-md);
    margin-top: var(--space-xl);
}

.podcast-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-md) var(--space-xl);
    background: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-ui);
    font-weight: 500;
    transition: var(--transition-base);
}

.podcast-link:hover {
    background: var(--color-accent-lighter);
    border-color: var(--color-accent-medium);
}

.podcast-link span {
    font-size: 1.5rem;
}
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
