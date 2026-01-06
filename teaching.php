<?php
declare(strict_types=1);

/**
 * Teaching & Training Page - Giảng dạy & Đào tạo
 * 
 * Showcase of teaching activities and training programs
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
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

<div class="teaching-page">
    <div class="container">
        <div class="content-width">
            <header class="page-header">
                <h1>Giảng dạy & Đào tạo</h1>
                <p class="page-intro">
                    Phần Giảng dạy & Đào tạo giới thiệu các hoạt động giảng dạy và huấn luyện mà tôi đã và đang tham gia 
                    trong bối cảnh đại học, đào tạo chuyên môn và phát triển con người. Công việc của tôi tập trung vào 
                    việc kết nối kiến thức tâm lý học với trải nghiệm thực tế của người học, đặc biệt là người trưởng thành 
                    đang làm việc và học tập trong các môi trường đa dạng.
                </p>
            </header>
            
            <div class="teaching-content">
                <section class="teaching-section">
                    <h2>Phương pháp giảng dạy</h2>
                    <p>
                        Các chương trình giảng dạy và đào tạo được thiết kế dựa trên nền tảng tâm lý học ứng dụng và chánh niệm, 
                        với mục tiêu hỗ trợ người học hiểu rõ hơn trải nghiệm của chính mình, phát triển năng lực tự học, 
                        tự điều chỉnh và làm việc có ý nghĩa trong đời sống và nghề nghiệp.
                    </p>
                </section>
                
                <section class="teaching-section">
                    <h2>Lĩnh vực giảng dạy</h2>
                    <div class="teaching-areas">
                        <div class="teaching-area">
                            <h3>Tâm lý học ứng dụng</h3>
                            <p>
                                Giảng dạy các môn học về tâm lý học cơ bản, tâm lý học phát triển, tâm lý học xã hội 
                                và ứng dụng tâm lý học trong đời sống và công việc.
                            </p>
                        </div>
                        
                        <div class="teaching-area">
                            <h3>Chánh niệm & Mindfulness</h3>
                            <p>
                                Workshop và khóa đào tạo về chánh niệm trong bối cảnh tâm lý học, hướng dẫn thực hành 
                                chánh niệm cho người trưởng thành và ứng dụng vào công việc.
                            </p>
                        </div>
                        
                        <div class="teaching-area">
                            <h3>Giáo dục người lớn</h3>
                            <p>
                                Đào tạo về phương pháp giảng dạy người trưởng thành, adult learning, experiential learning 
                                và reflective practice trong giáo dục và phát triển con người.
                            </p>
                        </div>
                        
                        <div class="teaching-area">
                            <h3>Phát triển nghề nghiệp</h3>
                            <p>
                                Coaching và mentoring cho giảng viên, chuyên gia tâm lý, và những người làm việc 
                                trong lĩnh vực giáo dục - đào tạo.
                            </p>
                        </div>
                    </div>
                </section>
                
                <section class="teaching-section">
                    <h2>Hợp tác & Dự án</h2>
                    <p>
                        Tôi mở cho các cơ hội hợp tác trong:
                    </p>
                    <ul>
                        <li>Giảng dạy tại các trường đại học và tổ chức giáo dục</li>
                        <li>Đào tạo doanh nghiệp về mindfulness, wellbeing và phát triển con người</li>
                        <li>Workshop và seminar về tâm lý học ứng dụng</li>
                        <li>Tư vấn chương trình đào tạo cho tổ chức</li>
                        <li>Hợp tác nghiên cứu trong lĩnh vực tâm lý học và giáo dục người lớn</li>
                    </ul>
                </section>
                
                <section class="teaching-section">
                    <h2>Liên hệ hợp tác</h2>
                    <p>
                        Nếu bạn quan tâm đến việc mời giảng, tổ chức workshop, hoặc hợp tác về đào tạo, 
                        vui lòng liên hệ qua <a href="/contact.php">trang liên hệ</a> hoặc email trực tiếp: 
                        <a href="mailto:<?= htmlspecialchars(FROM_EMAIL) ?>"><?= htmlspecialchars(FROM_EMAIL) ?></a>
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.teaching-page {
    padding: var(--space-4xl) 0;
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-5xl);
}

.teaching-content {
    max-width: var(--content-max);
    margin: 0 auto;
}

.teaching-section {
    margin-bottom: var(--space-5xl);
}

.teaching-section:last-child {
    margin-bottom: 0;
}

.teaching-section ul {
    list-style-position: outside;
    padding-left: var(--space-xl);
}

.teaching-section li {
    margin-bottom: var(--space-md);
}

.teaching-areas {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-2xl);
    margin-top: var(--space-xl);
}

@media (min-width: 768px) {
    .teaching-areas {
        grid-template-columns: repeat(2, 1fr);
    }
}

.teaching-area {
    padding: var(--space-xl);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border);
}

.teaching-area h3 {
    color: var(--color-accent-dark);
    font-size: 1.25rem;
    margin-bottom: var(--space-md);
}

.teaching-area p {
    margin: 0;
    color: var(--color-text-secondary);
}
</style>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
