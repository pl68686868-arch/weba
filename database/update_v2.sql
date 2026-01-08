-- Database Update V2 - Admin Features Support
-- Run this script to update your existing database without losing data.

-- 1. Create Media Table (if not exists)
CREATE TABLE IF NOT EXISTS media (
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

-- 2. Create Page Views Table (if not exists) for Analytics
CREATE TABLE IF NOT EXISTS page_views (
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

-- 3. Ensure Users Table has 'role' column
-- Note: This is a safe check. If column exists, it might error or do nothing depending on MySQL version/strict mode.
-- Better to run this manually if needed:
-- ALTER TABLE users ADD COLUMN role ENUM('admin', 'editor', 'author') DEFAULT 'author';

-- 4. Create Popular Content Table (if not exists)
CREATE TABLE IF NOT EXISTS popular_content (
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
