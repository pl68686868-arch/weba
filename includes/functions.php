<?php
declare(strict_types=1);

/**
 * Helper functions for common operations
 * 
 * @package Weba
 * @author Danny Duong
 */

/**
 * Sanitize string for output (XSS prevention)
 * 
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function escape(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate reading time for content
 * 
 * @param string $content HTML or text content
 * @param int $wordsPerMinute Average reading speed (default: 200)
 * @return int Reading time in minutes
 */
function calculateReadingTime(string $content, int $wordsPerMinute = 200): int {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / $wordsPerMinute);
    return max(1, $minutes); // Minimum 1 minute
}

/**
 * Generate excerpt from content
 * 
 * @param string $content Full content
 * @param int $length Excerpt length in characters
 * @param string $more Suffix for truncated text
 * @return string Excerpt
 */
function generateExcerpt(string $content, int $length = 200, string $more = '...'): string {
    $content = strip_tags($content);
    $content = trim($content);
    
    if (mb_strlen($content, 'UTF-8') <= $length) {
        return $content;
    }
    
    $excerpt = mb_substr($content, 0, $length, 'UTF-8');
    
    // Find last complete word
    $lastSpace = mb_strrpos($excerpt, ' ', 0, 'UTF-8');
    if ($lastSpace !== false) {
        $excerpt = mb_substr($excerpt, 0, $lastSpace, 'UTF-8');
    }
    
    return $excerpt . $more;
}

/**
 * Format date in Vietnamese
 * 
 * @param string $date Date string
 * @param string $format Format (short, long, relative)
 * @return string Formatted date
 */
function formatDate(string $date, string $format = 'short'): string {
    $timestamp = strtotime($date);
    
    if ($format === 'relative') {
        return getRelativeTime($timestamp);
    }
    
    $day = date('d', $timestamp);
    $month = date('m', $timestamp);
    $year = date('Y', $timestamp);
    
    $monthNames = [
        '01' => 'Tháng 1', '02' => 'Tháng 2', '03' => 'Tháng 3',
        '04' => 'Tháng 4', '05' => 'Tháng 5', '06' => 'Tháng 6',
        '07' => 'Tháng 7', '08' => 'Tháng 8', '09' => 'Tháng 9',
        '10' => 'Tháng 10', '11' => 'Tháng 11', '12' => 'Tháng 12'
    ];
    
    if ($format === 'long') {
        return "{$day} {$monthNames[$month]}, {$year}";
    }
    
    return "{$day}/{$month}/{$year}";
}

/**
 * Get relative time (e.g., "2 giờ trước")
 * 
 * @param int $timestamp Unix timestamp
 * @return string Relative time string
 */
function getRelativeTime(int $timestamp): string {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "{$minutes} phút trước";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "{$hours} giờ trước";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "{$days} ngày trước";
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return "{$weeks} tuần trước";
    } else {
        return formatDate(date('Y-m-d H:i:s', $timestamp), 'short');
    }
}

/**
 * Generate breadcrumb navigation
 * 
 * @param array $breadcrumbs Array of ['name' => 'Name', 'url' => 'URL']
 * @return string HTML breadcrumb
 */
function generateBreadcrumbs(array $breadcrumbs): string {
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb"  aria-label="Breadcrumb"><ol class="breadcrumb__list">';
    
    $count = count($breadcrumbs);
    $index = 1;
    
    foreach ($breadcrumbs as $crumb) {
        $isLast = ($index === $count);
        $html .= '<li class="breadcrumb__item">';
        
        if (!$isLast && !empty($crumb['url'])) {
            $html .= '<a href="' . escape($crumb['url']) . '">' . escape($crumb['name']) . '</a>';
        } else {
            $html .= '<span aria-current="page">' . escape($crumb['name']) . '</span>';
        }
        
        $html .= '</li>';
        $index++;
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * Get related posts based on tags and category
 * 
 * @param int $postId Current post ID
 * @param int $categoryId Category ID
 * @param int $limit Number of posts to return
 * @return array Related posts
 */
function getRelatedPosts(int $postId, int $categoryId, int $limit = 5): array {
    $db = Database::getInstance();
    
    // Get tags of current post
    $tagIds = $db->fetchAll(
        "SELECT tag_id FROM post_tags WHERE post_id = :postId",
        ['postId' => $postId]
    );
    
    $tagIdsArray = array_column($tagIds, 'tag_id');
    
    if (empty($tagIdsArray)) {
        // No tags, just get posts from same category
        return $db->fetchAll(
            "SELECT p.*, u.full_name as author_name, c.name as category_name
             FROM posts p
             JOIN users u ON p.author_id = u.id
             JOIN categories c ON p.category_id = c.id
             WHERE p.category_id = :categoryId 
             AND p.id != :postId
             AND p.status = 'published'
             ORDER BY p.published_at DESC
             LIMIT :limit",
            ['categoryId' => $categoryId, 'postId' => $postId, 'limit' => $limit]
        );
    }
    
    // Get posts with similar tags
    $placeholders = implode(',', array_fill(0, count($tagIdsArray), '?'));
    $params = $tagIdsArray;
    $params[] = $postId;
    $params[] = $limit;
    
    $sql = "SELECT p.*, u.full_name as author_name, c.name as category_name,
            COUNT(pt.tag_id) as matching_tags
            FROM posts p
            JOIN post_tags pt ON p.id = pt.post_id
            JOIN users u ON p.author_id = u.id
            JOIN categories c ON p.category_id = c.id
            WHERE pt.tag_id IN ({$placeholders})
            AND p.id != ?
            AND p.status = 'published'
            GROUP BY p.id
            ORDER BY matching_tags DESC, p.published_at DESC
            LIMIT ?";
    
    return $db->fetchAll($sql, $params);
}

/**
 * Get popular posts
 * 
 * @param int $limit Number of posts
 * @param string $period Period (day, week, month, all)
 * @return array Popular posts
 */
function getPopularPosts(int $limit = 5, string $period = 'week'): array {
    $db = Database::getInstance();
    
    $sql = "SELECT p.*, pc.views_count, u.full_name as author_name
            FROM popular_content pc
            JOIN posts p ON pc.post_id = p.id
            JOIN users u ON p.author_id = u.id
            WHERE pc.period = :period
            AND p.status = 'published'
            ORDER BY pc.views_count DESC
            LIMIT :limit";
    
    return $db->fetchAll($sql, ['period' => $period, 'limit' => $limit]);
}

/**
 * Track page view
 * 
 * @param int|null $postId Post ID (optional)
 * @param string $url Current URL
 * @return void
 */
function trackPageView(?int $postId, string $url): void {
    try {
        $db = Database::getInstance();
        
        $data = [
            'post_id' => $postId,
            'url' => $url,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
        
        $db->insert('page_views', $data);
        
        // Update post view count
        if ($postId) {
            $db->query(
                "UPDATE posts SET view_count = view_count + 1 WHERE id = :id",
                ['id' => $postId]
            );
        }
    } catch (Exception $e) {
        // Silent fail for analytics
        error_log('Track page view error: ' . $e->getMessage());
    }
}

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code (301, 302, etc.)
 * @return void
 */
function redirect(string $url, int $statusCode = 302): void {
    http_response_code($statusCode);
    header("Location: {$url}");
    exit;
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjaxRequest(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Return JSON response
 * 
 * @param mixed $data Data to return
 * @param int $statusCode HTTP status code
 * @return void
 */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Get current language
 * 
 * @return string Language code
 */
function getCurrentLanguage(): string {
    return $_SESSION['current_language'] ?? DEFAULT_LANGUAGE;
}

/**
 * Set current language
 * 
 * @param string $langCode Language code
 * @return void
 */
function setCurrentLanguage(string $langCode): void {
    if (in_array($langCode, AVAILABLE_LANGUAGES)) {
        $_SESSION['current_language'] = $langCode;
    }
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize filename for safe upload
 * 
 * @param string $filename Original filename
 * @return string Safe filename
 */
function sanitizeFilename(string $filename): string {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    
    // Create slug from basename
    $basename = SEO::createSlug($basename);
    
    // Add timestamp to ensure uniqueness
    $timestamp = time();
    
    return "{$basename}-{$timestamp}.{$extension}";
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIP(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}

/**
 * Rate limit check
 * 
 * @param string $identifier Identifier (IP, user ID, etc.)
 * @param string $action Action being rate limited
 * @param int $maxRequests Max requests allowed
 * @param int $windowSeconds Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function checkRateLimit(string $identifier, string $action, int $maxRequests = 60, int $windowSeconds = 60): bool {
    if (!RATE_LIMIT_ENABLED) {
        return true;
    }
    
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT request_count, window_start 
                FROM api_rate_limits 
                WHERE ip_address = :identifier 
                AND endpoint = :action 
                LIMIT 1";
        
        $limit = $db->fetchOne($sql, ['identifier' => $identifier, 'action' => $action]);
        
        $now = time();
        
        if (!$limit) {
            // First request, create new entry
            $db->insert('api_rate_limits', [
                'ip_address' => $identifier,
                'endpoint' => $action,
                'request_count' => 1,
                'window_start' => date('Y-m-d H:i:s')
            ]);
            return true;
        }
        
        $windowStart = strtotime($limit['window_start']);
        $elapsed = $now - $windowStart;
        
        if ($elapsed > $windowSeconds) {
            // Window expired, reset
            $db->update(
                'api_rate_limits',
                ['request_count' => 1, 'window_start' => date('Y-m-d H:i:s')],
                'ip_address = :identifier AND endpoint = :action',
                ['identifier' => $identifier, 'action' => $action]
            );
            return true;
        }
        
        if ($limit['request_count'] >= $maxRequests) {
            // Rate limit exceeded
            return false;
        }
        
        // Increment count
        $db->query(
            "UPDATE api_rate_limits 
             SET request_count = request_count + 1 
             WHERE ip_address = :identifier AND endpoint = :action",
            ['identifier' => $identifier, 'action' => $action]
        );
        
        return true;
        
    } catch (Exception $e) {
        error_log('Rate limit check error: ' . $e->getMessage());
        return true; // Fail open
    }
}
