<?php
declare(strict_types=1);

/**
 * Production Configuration for Azdigi Hosting
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to config.php on your hosting
 * 2. Update DB_PASS with your actual MySQL password
 * 3. Update SITE_URL if using different domain
 * 
 * @package Weba
 * @author Danny Duong
 */

// ============================================================================
// ERROR REPORTING - PRODUCTION
// ============================================================================
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Hidden in production
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ============================================================================
// DATABASE CONFIGURATION - AZDIGI HOSTING
// ============================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'iwzwumvq_weba');
define('DB_USER', 'iwzwumvq_dmin');
define('DB_PASS', 'W3b@2024$ecur3!Ps'); // ← UPDATE THIS WITH YOUR ACTUAL PASSWORD
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// SITE CONFIGURATION
// ============================================================================
define('SITE_URL', 'https://duongtranminhdoan.com');
define('SITE_NAME', 'Dương Trần Minh Đoan');
define('SITE_TAGLINE', 'Giảng viên, người thực hành tâm lý và chánh niệm');
define('ADMIN_EMAIL', 'pl68686868@gmail.com');

// ============================================================================
// PATHS - AZDIGI HOSTING
// ============================================================================
define('BASE_PATH', '/home/iwzwumvq/public_html');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('CACHE_PATH', BASE_PATH . '/cache');
define('LOG_PATH', BASE_PATH . '/logs');

// ============================================================================
// URLS
// ============================================================================
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOAD_URL', SITE_URL . '/uploads');

// ============================================================================
// SECURITY
// ============================================================================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 7200); // 2 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutes

// ============================================================================
// FILE UPLOAD
// ============================================================================
define('MAX_IMAGE_SIZE', 5242880); // 5MB
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_FILE_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// ============================================================================
// PAGINATION
// ============================================================================
define('POSTS_PER_PAGE', 10);
define('COMMENTS_PER_PAGE', 20);

// ============================================================================
// CACHE SETTINGS
// ============================================================================
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour
define('PAGE_CACHE_LIFETIME', 300); // 5 minutes

// ============================================================================
// RATE LIMITING
// ============================================================================
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_REQUESTS', 60);
define('RATE_LIMIT_WINDOW', 60);

// ============================================================================
// EMAIL CONFIGURATION
// ============================================================================
define('SMTP_ENABLED', false); // Set to true when configuring SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@duongtranminhdoan.com');
define('FROM_NAME', SITE_NAME);

// ============================================================================
// THIRD-PARTY APIs
// ============================================================================
define('GOOGLE_ANALYTICS_ID', ''); // Add your GA4 tracking ID
define('RECAPTCHA_SITE_KEY', '');
define('RECAPTCHA_SECRET_KEY', '');

// ============================================================================
// SEO DEFAULTS
// ============================================================================
define('DEFAULT_META_DESCRIPTION', 'Không gian chia sẻ về tâm lý học, chánh niệm và phát triển con người.');
define('DEFAULT_META_KEYWORDS', 'tâm lý học, chánh niệm, mindfulness, giáo dục người lớn');
define('DEFAULT_OG_IMAGE', ASSETS_URL . '/images/og-default.jpg');

// ============================================================================
// MULTILINGUAL
// ============================================================================
define('DEFAULT_LANGUAGE', 'vi');
define('AVAILABLE_LANGUAGES', ['vi', 'en']);

// ============================================================================
// ENVIRONMENT
// ============================================================================
define('ENVIRONMENT', 'production');

// ============================================================================
// AUTO-CREATE DIRECTORIES
// ============================================================================
$requiredDirs = [UPLOAD_PATH, CACHE_PATH, LOG_PATH];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ============================================================================
// SESSION CONFIGURATION
// ============================================================================
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1'); // Requires HTTPS
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
