<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Panel | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin-style.css">
    <script>
        if (localStorage.getItem('admin_theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark'); // Use documentElement for earlier application
        }
    </script>
</head>
<body class="admin-body">
    <div class="admin-layout">
        <!-- Mobile Sidebar Toggle -->
        <button id="sidebarToggle" class="mobile-toggle">
            ☰
        </button>
        <div id="sidebarOverlay" class="sidebar-overlay"></div>

        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <button id="sidebarClose" class="sidebar-close">&times;</button>
            <div class="admin-brand">
                <h2><?= htmlspecialchars(SITE_NAME) ?></h2>
                <p>Quản trị hệ thống</p>
            </div>
            
            <nav class="admin-nav">
                <a href="/admin/dashboard.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="/admin/posts.php" class="admin-nav__link <?= (basename($_SERVER['PHP_SELF']) === 'posts.php' || basename($_SERVER['PHP_SELF']) === 'posts-new.php' || basename($_SERVER['PHP_SELF']) === 'posts-edit.php') && !(isset($_GET['type']) && $_GET['type'] === 'podcast') ? 'active' : '' ?>">
                    <span class="nav-icon">🖋️</span> Bài viết
                </a>
                <a href="/admin/categories.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📁</span> Chuyên mục
                </a>
                <a href="/admin/posts.php?type=podcast" class="admin-nav__link <?= (isset($_GET['type']) && $_GET['type'] === 'podcast') ? 'active' : '' ?>">
                    <span class="nav-icon">🎙️</span> Podcast & Dự án
                </a>
                <a href="/admin/media.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🖼️</span> Thư viện Media
                </a>
                <a href="/admin/comments.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'comments.php' ? 'active' : '' ?>">
                    <span class="nav-icon">💬</span> Bình luận
                </a>
                <a href="/admin/newsletter.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'newsletter.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📧</span> Bản tin
                </a>
                <a href="/admin/users.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'users.php' || basename($_SERVER['PHP_SELF']) === 'users-new.php' || basename($_SERVER['PHP_SELF']) === 'users-edit.php' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span> Người dùng
                </a>
                
                <hr class="admin-nav__divider">
                
                <a href="/admin/settings.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span> Cài đặt
                </a>
                <a href="/admin/appearance.php" class="admin-nav__link <?= basename($_SERVER['PHP_SELF']) === 'appearance.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🎨</span> Giao diện
                </a>
                
                <hr class="admin-nav__divider">
                
                <a href="/" class="admin-nav__link" target="_blank">
                    <span class="nav-icon">🌐</span> Website
                </a>
                <a href="#" id="darkModeToggle" class="admin-nav__link">
                    <span class="nav-icon">🌓</span> Giao diện tối
                </a>
                <a href="/admin/logout.php" class="admin-nav__link sign-out">
                    <span class="nav-icon">🚪</span> Đăng xuất
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-container">
