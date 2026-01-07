    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer__grid">
                <!-- Column 1: Brand & Intro -->
                <div class="footer__brand-col">
                    <h2 class="footer__logo"><?= htmlspecialchars(SITE_NAME) ?></h2>
                    <p class="footer__desc">
                        Website này là không gian chia sẻ những suy tư, kiến thức và trải nghiệm thực hành 
                        dựa trên nền tảng tâm lý học và chánh niệm.
                    </p>
                    <p class="footer__copyright">
                        &copy; 2026 Dương Trần Minh Đoàn. <br>Mọi quyền được bảo lưu.
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
                        Nhận bài viết mới nhất qua email. Không spam, chỉ có sự chia sẻ.
                    </p>
                    <form action="/subscribe.php" method="POST" class="newsletter-form">
                        <input type="email" name="email" placeholder="Email của bạn" required class="newsletter-input">
                        <button type="submit" class="newsletter-button">Đăng ký</button>
                    </form>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="<?= ASSETS_URL ?>/js/main.js" defer></script>
    
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
