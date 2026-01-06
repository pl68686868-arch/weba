<?php
declare(strict_types=1);

/**
 * RSS Feed Generator
 * 
 * Generates RSS 2.0 feed for latest posts
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Cache.php';
require_once __DIR__ . '/includes/functions.php';

// Set XML content type
header('Content-Type: application/rss+xml; charset=utf-8');

$cache = new Cache();
$db = Database::getInstance();

// Try to get cached feed
$feed = $cache->get('rss_feed', true);

if ($feed === false) {
    // Get latest 20 posts
    $posts = $db->fetchAll(
        "SELECT p.*, c.name as category_name, u.full_name as author_name
         FROM posts p
         JOIN categories c ON p.category_id = c.id
         JOIN users u ON p.author_id = u.id
         WHERE p.status = 'published'
         ORDER BY p.published_at DESC
         LIMIT 20"
    );
    
    // Build RSS feed
    $feed = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $feed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $feed .= '<channel>' . "\n";
    $feed .= '  <title>' . htmlspecialchars(SITE_NAME) . '</title>' . "\n";
    $feed .= '  <link>' . htmlspecialchars(SITE_URL) . '</link>' . "\n";
    $feed .= '  <description>' . htmlspecialchars(DEFAULT_META_DESCRIPTION) . '</description>' . "\n";
    $feed .= '  <language>vi</language>' . "\n";
    $feed .= '  <atom:link href="' . htmlspecialchars(SITE_URL . '/rss.php') . '" rel="self" type="application/rss+xml" />' . "\n";
    
    foreach ($posts as $post) {
        $postUrl = SITE_URL . '/post/' . $post['slug'];
        $pubDate = date('r', strtotime($post['published_at'] ?? $post['created_at']));
        
        $feed .= '  <item>' . "\n";
        $feed .= '    <title>' . htmlspecialchars($post['title']) . '</title>' . "\n";
        $feed .= '    <link>' . htmlspecialchars($postUrl) . '</link>' . "\n";
        $feed .= '    <guid isPermaLink="true">' . htmlspecialchars($postUrl) . '</guid>' . "\n";
        $feed .= '    <pubDate>' . $pubDate . '</pubDate>' . "\n";
        $feed .= '    <category>' . htmlspecialchars($post['category_name']) . '</category>' . "\n";
        $feed .= '    <author>' . htmlspecialchars(FROM_EMAIL) . ' (' . htmlspecialchars($post['author_name']) . ')</author>' . "\n";
        
        if ($post['excerpt']) {
            $feed .= '    <description><![CDATA[' . $post['excerpt'] . ']]></description>' . "\n";
        }
        
        $feed .= '  </item>' . "\n";
    }
    
    $feed .= '</channel>' . "\n";
    $feed .= '</rss>';
    
    // Cache for 1 hour
    $cache->set('rss_feed', $feed, 3600, true);
}

echo $feed;
