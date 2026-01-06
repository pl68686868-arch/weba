<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Panel | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin-style.css">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <h2><?= htmlspecialchars(SITE_NAME) ?></h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="admin-nav">
                <a href="/admin/dashboard.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    📊 Dashboard
                </a>
                <a href="/admin/posts.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'posts.php' || basename($_SERVER['PHP_SELF']) === 'posts-new.php' || basename($_SERVER['PHP_SELF']) === 'posts-edit.php' ? 'active' : '' ?>">
                    📄 Bài viết
                </a>
                <a href="/admin/categories.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                    📁 Chuyên mục
                </a>
                <a href="/admin/tags.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'tags.php' ? 'active' : '' ?>">
                    🏷️ Tags
                </a>
                <a href="/admin/media.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : '' ?>">
                    🖼️ Media
                </a>
                <a href="/admin/comments.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'comments.php' ? 'active' : '' ?>">
                    💬 Comments
                </a>
                <a href="/admin/newsletter.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'newsletter.php' ? 'active' : '' ?>">
                    📧 Newsletter
                </a>
                <a href="/admin/settings.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                    ⚙️ Cài đặt
                </a>
                
                <hr class="admin-nav__divider">
                
                <a href="/" class="admin-nav__link" target="_blank">
                    🌐 Xem website
                </a>
                <a href="/admin/logout.php" class="admin-nav__link">
                    🚪 Đăng xuất
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-container">
