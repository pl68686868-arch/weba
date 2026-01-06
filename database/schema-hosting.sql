-- ============================================================================
-- Premium Psychology & Mindfulness Website - Database Schema
-- Author: Danny Duong
-- Charset: UTF-8 with utf8mb4 for full Unicode support (including emojis)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing database if exists and create new

-- ============================================================================
-- CORE TABLES
-- ============================================================================

-- Users table for admin authentication
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'editor', 'author') DEFAULT 'author',
    two_factor_secret VARCHAR(32) DEFAULT NULL,
    two_factor_enabled TINYINT(1) DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table (The 4 Pillars)
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT UNSIGNED DEFAULT NULL,
    meta_title VARCHAR(60),
    meta_description VARCHAR(160),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent_id (parent_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags table for flexible content organization
CREATE TABLE tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    usage_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_usage_count (usage_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Languages table for multi-language support
CREATE TABLE languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts table with comprehensive fields
CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(255) DEFAULT NULL,
    category_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    language_id INT UNSIGNED DEFAULT NULL,
    status ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    reading_time INT UNSIGNED DEFAULT 0, -- in minutes
    view_count INT UNSIGNED DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    allow_comments TINYINT(1) DEFAULT 1,
    -- SEO fields
    meta_title VARCHAR(60),
    meta_description VARCHAR(160),
    meta_keywords VARCHAR(255),
    canonical_url VARCHAR(255),
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_category_id (category_id),
    INDEX idx_author_id (author_id),
    INDEX idx_is_featured (is_featured),
    INDEX idx_view_count (view_count),
    FULLTEXT idx_search (title, content, excerpt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post-Tags relationship (many-to-many)
CREATE TABLE post_tags (
    post_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post versions for version control
CREATE TABLE post_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    edited_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_post_id (post_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Translations table for multi-language content
CREATE TABLE translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_post_id INT UNSIGNED NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    meta_title VARCHAR(60),
    meta_description VARCHAR(160),
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (original_post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_translation (original_post_id, language_id),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Media table for file management
CREATE TABLE media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT UNSIGNED NOT NULL, -- in bytes
    mime_type VARCHAR(100) NOT NULL,
    alt_text VARCHAR(255),
    caption TEXT,
    -- Image-specific fields
    width INT UNSIGNED DEFAULT NULL,
    height INT UNSIGNED DEFAULT NULL,
    has_webp TINYINT(1) DEFAULT 0,
    -- Metadata
    uploaded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_file_type (file_type),
    INDEX idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table with nested support
CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(100) NOT NULL,
    author_website VARCHAR(255),
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam', 'trash') DEFAULT 'pending',
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEO & ANALYTICS TABLES
-- ============================================================================

-- SEO metadata per page
CREATE TABLE seo_metadata (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_type ENUM('post', 'page', 'category', 'tag', 'custom') DEFAULT 'custom',
    reference_id INT UNSIGNED DEFAULT NULL, -- ID of post/category/tag
    url_path VARCHAR(500) NOT NULL UNIQUE,
    meta_title VARCHAR(60),
    meta_description VARCHAR(160),
    meta_keywords VARCHAR(255),
    og_title VARCHAR(60),
    og_description VARCHAR(160),
    og_image VARCHAR(500),
    canonical_url VARCHAR(500),
    schema_json TEXT, -- JSON-LD structured data
    robots_meta VARCHAR(100) DEFAULT 'index, follow',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_page_type (page_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_url_path (url_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page views tracking
CREATE TABLE page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED DEFAULT NULL,
    url VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL,
    INDEX idx_post_id (post_id),
    INDEX idx_visited_at (visited_at),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Popular content cache
CREATE TABLE popular_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    views_count INT UNSIGNED DEFAULT 0,
    period ENUM('day', 'week', 'month', 'all') DEFAULT 'all',
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_period (post_id, period),
    INDEX idx_period (period),
    INDEX idx_views_count (views_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sitemap cache
CREATE TABLE sitemap_cache (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL UNIQUE,
    priority DECIMAL(2,1) DEFAULT 0.5,
    changefreq ENUM('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') DEFAULT 'weekly',
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 301 Redirects management
CREATE TABLE redirects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_url VARCHAR(500) NOT NULL UNIQUE,
    to_url VARCHAR(500) NOT NULL,
    status_code SMALLINT DEFAULT 301,
    hit_count INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_from_url (from_url),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ENGAGEMENT TABLES
-- ============================================================================

-- Newsletter subscribers
CREATE TABLE newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100),
    status ENUM('unconfirmed', 'active', 'unsubscribed', 'bounced') DEFAULT 'unconfirmed',
    verification_token VARCHAR(64),
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME DEFAULT NULL,
    unsubscribed_at DATETIME DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter campaigns
CREATE TABLE newsletter_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft', 'scheduled', 'sent') DEFAULT 'draft',
    scheduled_at DATETIME DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    recipients_count INT UNSIGNED DEFAULT 0,
    opened_count INT UNSIGNED DEFAULT 0,
    clicked_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookmarks (cookie-based, for logged out users)
CREATE TABLE bookmarks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    cookie_id VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_cookie_id (cookie_id),
    INDEX idx_post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social shares tracking
CREATE TABLE social_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    platform ENUM('facebook', 'twitter', 'linkedin', 'email', 'other') NOT NULL,
    share_count INT UNSIGNED DEFAULT 0,
    last_shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_platform (post_id, platform),
    INDEX idx_share_count (share_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CONFIGURATION & SYSTEM TABLES
-- ============================================================================

-- Site settings (key-value store)
CREATE TABLE site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache table
CREATE TABLE cache (
    cache_key VARCHAR(255) NOT NULL PRIMARY KEY,
    cache_value LONGTEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API rate limiting
CREATE TABLE api_rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_count INT UNSIGNED DEFAULT 0,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_endpoint (ip_address, endpoint),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INITIAL DATA
-- ============================================================================

-- Insert default admin user (password: admin123 - CHANGE THIS!)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'dannyduong.psycoach@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dương Trần Minh Đoan', 'admin');

-- Insert default languages
INSERT INTO languages (code, name, is_default, is_active) VALUES
('vi', 'Tiếng Việt', 1, 1),
('en', 'English', 0, 1);

-- Insert the 4 pillars as categories
INSERT INTO categories (name, slug, description, meta_title, meta_description, display_order) VALUES
('Tâm lý học & Đời sống trưởng thành', 'tam-ly-hoc-doi-song-truong-thanh', 
 'Viết về lo âu, cô đơn, khủng hoảng tuổi trung niên, động lực, ý nghĩa sống và sự trưởng thành tâm lý. Tâm lý học không chữa bệnh mà nâng đỡ con người.',
 'Tâm lý học & Đời sống trưởng thành', 
 'Bài viết về tâm lý học ứng dụng, động lực, ý nghĩa sống và sự trưởng thành nội tâm của người lớn.', 
 1),

('Chánh niệm & Hồi phục thân–tâm', 'chanh-niem-hoi-phuc-than-tam',
 'Chánh niệm đúng nghĩa, không thần thánh hóa. Thực hành nhỏ: thở, dừng, nhận diện cảm xúc. Sự hồi phục nội tâm ở người trưởng thành.',
 'Chánh niệm & Hồi phục thân–tâm',
 'Thực hành chánh niệm và hồi phục nội tâm cho người trưởng thành, tiếp cận từ tâm lý học.',
 2),

('Giáo dục & Học tập người trưởng thành', 'giao-duc-hoc-tap-nguoi-truong-thanh',
 'Học ở tuổi trưởng thành khác gì học sinh viên. Vai trò của phản tư, trải nghiệm, chánh niệm trong học tập.',
 'Giáo dục & Học tập người trưởng thành',
 'Góc nhìn về giáo dục người lớn, học suốt đời và học tập có ý thức.',
 3),

('Phản tư nghề nghiệp', 'phan-tu-nghe-nghiep',
 'Những lúc nghi ngờ bản thân. Những xung đột nghề nghiệp. Cảm giác đứng giữa học thuật, thị trường và chữa lành.',
 'Phản tư nghề nghiệp',
 'Suy tư cá nhân về hành trình làm nghề giảng dạy và thực hành tâm lý.',
 4);

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Dương Trần Minh Đoan', 'string', 'Website name'),
('site_tagline', 'Giảng viên & Người thực hành tâm lý', 'string', 'Site tagline'),
('site_description', 'Không gian chia sẻ về tâm lý học, chánh niệm và phát triển con người.', 'string', 'Site meta description'),
('posts_per_page', '10', 'number', 'Number of posts per page'),
('enable_comments', '1', 'boolean', 'Enable comments globally'),
('enable_newsletter', '1', 'boolean', 'Enable newsletter subscription'),
('google_analytics_id', '', 'string', 'Google Analytics tracking ID'),
('cdn_url', '', 'string', 'CDN URL for assets');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
