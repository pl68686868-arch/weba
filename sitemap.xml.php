<?php
declare(strict_types=1);

/**
 * XML Sitemap Generator
 * 
 * Generates XML sitemap for search engines
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SEO.php';
require_once __DIR__ . '/includes/Cache.php';

// Set XML content type
header('Content-Type: application/xml; charset=utf-8');

$cache = new Cache();

// Try to get cached sitemap
$sitemap = $cache->get('sitemap_xml', true);

if ($sitemap === false) {
    // Generate new sitemap
    $seo = new SEO();
    $sitemap = $seo->generateSitemap();
    
    // Cache for 24 hours
    $cache->set('sitemap_xml', $sitemap, 86400, true);
}

echo $sitemap;
