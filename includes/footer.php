    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__text">
                    <p>
                        Website này là một không gian chia sẻ được hình thành từ việc giảng dạy, thực hành tâm lý và chánh niệm. 
                        Mỗi bài viết là một lời mời dừng lại, quan sát và hiểu mình sâu hơn trong đời sống và công việc. 
                        Nếu bạn đang ở trong hành trình tìm kiếm ý nghĩa và sự hồi phục thân–tâm, 
                        mong rằng bạn sẽ tìm thấy ở đây một điểm tựa nhẹ nhàng.
                    </p>
                </div>
                
                <div class="footer__newsletter">
                    <h3 class="footer__newsletter-title">Nhận bài viết mới qua email</h3>
                    <form class="newsletter-form" id="newsletter-form">
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="Email của bạn" 
                            required
                            class="newsletter-input"
                        >
                        <button type="submit" class="newsletter-button">Đăng ký</button>
                    </form>
                    <p class="newsletter-note">Không spam. Chỉ những chia sẻ ý nghĩa.</p>
                </div>
                
                <nav class="footer__nav" aria-label="Footer Navigation">
                    <ul class="footer-nav-list">
                        <li><a href="/">Trang chủ</a></li>
                        <li><a href="/about.php">Giới thiệu</a></li>
                        <li><a href="/writing.php">Viết & Chia sẻ</a></li>
                        <li><a href="/contact.php">Liên hệ</a></li>
                        <li><a href="/rss.php">RSS</a></li>
                    </ul>
                </nav>
                
                <div class="footer__bottom">
                    <p class="copyright">
                        © <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. Mọi quyền được bảo lưu.
                    </p>
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
