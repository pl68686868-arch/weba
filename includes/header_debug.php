<?php
declare(strict_types=1);

echo "<!-- DEBUG: Header check 1 - Start -->\n";

// Ensure config is loaded
if (!defined('SITE_NAME')) {
    echo "<!-- DEBUG: Loading config -->\n";
    require_once __DIR__ . '/../config/config.php';
}

echo "<!-- DEBUG: Header check 2 - Defined check passed -->\n";

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// SEO object should be passed from parent page
global $seo;

echo "<!-- DEBUG: Header check 3 - Before HTML -->\n";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- DEBUG: Header check 4 - After viewport -->
    
    <?php if (isset($seo)): ?>
        <?php echo "<!-- DEBUG: Rendering Meta -->"; ?>
        <?= $seo->renderMetaTags() ?>
        <?= $seo->renderSchema() ?>
    <?php else: ?>
        <title><?= htmlspecialchars(SITE_NAME) ?></title>
        <meta name="description" content="<?= htmlspecialchars(DEFAULT_META_DESCRIPTION) ?>">
    <?php endif; ?>
    
    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Crimson+Text:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/favicon.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <?php if (defined('GOOGLE_ANALYTICS_ID') && !empty(GOOGLE_ANALYTICS_ID)): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= GOOGLE_ANALYTICS_ID ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= GOOGLE_ANALYTICS_ID ?>');
    </script>
    <?php endif; ?>
    <!-- DEBUG: Header check 5 - End Head -->
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-link">Chuyển đến nội dung chính</a>
    
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header__inner">
                <div class="header__brand">
                    <a href="/" class="brand-link">
                        <h1 class="brand-name"><?= htmlspecialchars(SITE_NAME) ?></h1>
                        <p class="brand-tagline"><?= htmlspecialchars(SITE_TAGLINE) ?></p>
                    </a>
                </div>
                
                <button class="mobile-menu-toggle" aria-label="Mở menu" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <nav class="main-nav" aria-label="Main Navigation">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="/" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                                Trang chủ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/about.php" class="nav-link <?= $currentPage === 'about' ? 'active' : '' ?>">
                                Giới thiệu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/writing.php" class="nav-link <?= $currentPage === 'writing' ? 'active' : '' ?>">
                                Viết & Chia sẻ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/teaching.php" class="nav-link <?= $currentPage === 'teaching' ? 'active' : '' ?>">
                                Giảng dạy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/podcast.php" class="nav-link <?= $currentPage === 'podcast' ? 'active' : '' ?>">
                                Podcast
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/contact.php" class="nav-link <?= $currentPage === 'contact' ? 'active' : '' ?>">
                                Liên hệ
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    <!-- DEBUG: Header check 6 - End Header -->
    
    <!-- Main Content -->
    <main id="main-content" class="main-content">
<?php echo "<!-- DEBUG: Header check 7 - End File -->\n"; ?>
