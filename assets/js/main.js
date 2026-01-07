/**
 * Main JavaScript - Frontend Interactions
 * 
 * Features:
 * - Mobile menu toggle
 * - Smooth scrolling
 * - Newsletter form handling
 * - Reading progress indicator
 * - Lazy loading images
 * - Fade-in animations on scroll
 * 
 * @author Danny Duong
 */

(function () {
    'use strict';

    // ========================================================================
    // MOBILE MENU TOGGLE
    // ========================================================================

    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');

    if (mobileMenuToggle && mainNav) {
        mobileMenuToggle.addEventListener('click', function () {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            this.classList.toggle('is-open');
            mainNav.classList.toggle('is-open');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function (e) {
            if (!mainNav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                mainNav.classList.remove('is-open');
                mobileMenuToggle.classList.remove('is-open');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close menu when clicking a link
        mainNav.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                mainNav.classList.remove('is-open');
                mobileMenuToggle.classList.remove('is-open');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // ========================================================================
    // SMOOTH SCROLLING FOR ANCHOR LINKS
    // ========================================================================

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;

            e.preventDefault();
            const target = document.querySelector(href);

            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ========================================================================
    // NEWSLETTER FORM HANDLING
    // ========================================================================

    const newsletterForm = document.getElementById('newsletter-form');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const emailInput = this.querySelector('input[name="email"]');
            const button = this.querySelector('button[type="submit"]');
            const email = emailInput.value.trim();

            if (!email) return;

            // Disable button during submission
            button.disabled = true;
            button.textContent = 'Đang xử lý...';

            try {
                const response = await fetch('/api/newsletter.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('Cảm ơn! Vui lòng kiểm tra email để xác nhận đăng ký.', 'success');
                    emailInput.value = '';
                } else {
                    showMessage(data.message || 'Đã có lỗi xảy ra. Vui lòng thử lại.', 'error');
                }
            } catch (error) {
                showMessage('Không thể kết nối. Vui lòng thử lại sau.', 'error');
            } finally {
                button.disabled = false;
                button.textContent = 'Đăng ký';
            }
        });
    }

    // ========================================================================
    // READING PROGRESS INDICATOR (for article pages)
    // ========================================================================

    const article = document.querySelector('.article-content');

    if (article) {
        const progressBar = createProgressBar();

        window.addEventListener('scroll', function () {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            const scrollPercentage = (scrollTop / (documentHeight - windowHeight)) * 100;
            progressBar.style.width = Math.min(scrollPercentage, 100) + '%';
        });
    }

    function createProgressBar() {
        const bar = document.createElement('div');
        bar.style.position = 'fixed';
        bar.style.top = '0';
        bar.style.left = '0';
        bar.style.height = '3px';
        bar.style.background = '#3A7D6B';
        bar.style.width = '0%';
        bar.style.zIndex = '100';
        bar.style.transition = 'width 0.2s ease';
        document.body.appendChild(bar);
        return bar;
    }

    // ========================================================================
    // FADE-IN ON SCROLL ANIMATION
    // ========================================================================

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const fadeInObserver = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                fadeInObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements
    document.querySelectorAll('.pillar-card, .article-card').forEach(el => {
        fadeInObserver.observe(el);
    });

    // ========================================================================
    // LAZY LOADING IMAGES (native lazy loading fallback)
    // ========================================================================

    if ('loading' in HTMLImageElement.prototype) {
        // Browser supports native lazy loading
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
        });
    } else {
        // Fallback for older browsers
        const imageObserver = new IntersectionObserver(function (entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[loading="lazy"]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // ========================================================================
    // SOCIAL SHARE TRACKING
    // ========================================================================

    document.querySelectorAll('[data-share]').forEach(button => {
        button.addEventListener('click', function () {
            const platform = this.dataset.share;
            const postId = this.dataset.postId;

            if (postId) {
                // Track share via API
                fetch('/api/share.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ post_id: postId, platform })
                }).catch(() => {
                    // Silent fail for analytics
                });
            }
        });
    });

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    function showMessage(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.padding = '16px 24px';
        alert.style.borderRadius = '8px';
        alert.style.zIndex = '1000';
        alert.style.boxShadow = '0 4px 16px rgba(0,0,0,0.1)';
        alert.style.maxWidth = '400px';

        if (type === 'success') {
            alert.style.background = '#EFE';
            alert.style.color = '#3A3';
            alert.style.border = '1px solid #CFC';
        } else if (type === 'error') {
            alert.style.background = '#FEE';
            alert.style.color = '#C33';
            alert.style.border = '1px solid #FCC';
        } else {
            alert.style.background = '#EEF';
            alert.style.color = '#33C';
            alert.style.border = '1px solid #CCF';
        }

        document.body.appendChild(alert);

        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    // ========================================================================
    // TABLE OF CONTENTS GENERATOR (for long articles)
    // ========================================================================

    const articleContent = document.querySelector('.article-content');
    const tocContainer = document.querySelector('.table-of-contents');

    if (articleContent && tocContainer) {
        const headings = articleContent.querySelectorAll('h2, h3');

        if (headings.length > 3) {
            const toc = document.createElement('ul');
            toc.className = 'toc-list';

            headings.forEach((heading, index) => {
                // Add ID to heading if doesn't exist
                if (!heading.id) {
                    heading.id = `heading-${index}`;
                }

                const li = document.createElement('li');
                li.className = heading.tagName.toLowerCase() === 'h3' ? 'toc-item toc-item--sub' : 'toc-item';

                const link = document.createElement('a');
                link.href = `#${heading.id}`;
                link.textContent = heading.textContent;
                link.className = 'toc-link';

                li.appendChild(link);
                toc.appendChild(li);
            });

            tocContainer.appendChild(toc);
        } else {
            // Hide TOC if too few headings
            tocContainer.style.display = 'none';
        }
    }

})();
