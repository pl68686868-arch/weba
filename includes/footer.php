    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer__grid">
                <!-- Column 1: Brand & Intro -->
                <div class="footer__brand-col">
                    <h2 class="footer__logo"><?= htmlspecialchars(SITE_NAME) ?></h2>
                    <p class="footer__desc">
                        <?= escape(get_setting('footer_description', 'Website này là không gian chia sẻ những suy tư, kiến thức và trải nghiệm thực hành dựa trên nền tảng tâm lý học và chánh niệm.')) ?>
                    </p>
                    <p class="footer__copyright">
                        <?= escape(get_setting('footer_copyright', '© 2026 Dương Trần Minh Đoàn. Mọi quyền được bảo lưu.')) ?>
                    </p>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer__nav-col">
                    <h3 class="footer__heading">Điều hướng</h3>
                    <ul class="footer__nav-list">
                        <li><a href="/">Trang chủ</a></li>
                        <li><a href="/about.php">Giới thiệu</a></li>
                        <li><a href="/writing.php">Viết & Chia sẻ</a></li>
                        <li><a href="/teaching.php">Giảng dạy</a></li>
                        <li><a href="/podcast.php">Podcast</a></li>
                        <li><a href="/contact.php">Liên hệ</a></li>
                    </ul>
                </div>

                <!-- Column 3: Newsletter -->
                <div class="footer__newsletter-col">
                    <h3 class="footer__heading">Kết nối</h3>
                    <p class="footer__newsletter-desc">
                        <?= escape(get_setting('footer_newsletter_desc', 'Nhận bài viết mới nhất qua email. Không spam, chỉ có sự chia sẻ.')) ?>
                    </p>
                    <form id="newsletter-form" action="/subscribe.php" method="POST" class="newsletter-form">
                        <?php
                        if (!class_exists('Auth')) {
                            require_once dirname(__DIR__) . '/includes/Auth.php';
                        }
                        if (!isset($auth) || !($auth instanceof Auth)) {
                            $auth = new Auth();
                        }
                        echo $auth->getCSRFInput();
                        ?>
                        <input type="email" name="email" placeholder="Email của bạn" required class="newsletter-input" id="newsletter-email">
                        <button type="submit" class="newsletter-button" id="newsletter-submit">Đăng ký</button>
                    </form>
                    <p id="newsletter-message" style="display:none; margin-top: 0.75rem; font-size: 0.875rem; font-family: var(--font-ui);"></p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="<?= ASSETS_URL ?>/js/main.js" defer></script>
    <script src="<?= ASSETS_URL ?>/js/subscribe-handler.js" defer></script>
    
    <!-- Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed'));
            });
        }
    </script>
</body>
</html>
