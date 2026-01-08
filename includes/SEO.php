<?php declare(strict_types=1);

/**
 * SEO utility class for meta tags and structured data generation
 * 
 * Features:
 * - Meta tag generation (title, description, keywords)
 * - Open Graph tags for social sharing
 * - Twitter Card tags
 * - Schema.org JSON-LD structured data
 * - Canonical URL handling
 * - Sitemap generation
 * 
 * @package Weba
 * @author Danny Duong
 */
class SEO {
    private Database $db;
    private array $metaTags = [];
    private array $ogTags = [];
    private array $schemaData = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Set page title
     * 
     * @param string $title Page title (50-60 characters recommended)
     * @param bool $appendSiteName Whether to append site name
     * @return self
     */
    public function setTitle(string $title, bool $appendSiteName = true): self {
        $fullTitle = $appendSiteName ? "{$title} | " . SITE_NAME : $title;
        $this->metaTags['title'] = $fullTitle;
        $this->ogTags['og:title'] = $title; // OG title without site name
        return $this;
    }

    /**
     * Set meta description
     * 
     * @param string $description Description (150-160 characters recommended)
     * @return self
     */
    public function setDescription(string $description): self {
        $this->metaTags['description'] = $description;
        $this->ogTags['og:description'] = $description;
        return $this;
    }

    /**
     * Set meta keywords
     * 
     * @param string|array $keywords Keywords (comma-separated string or array)
     * @return self
     */
    public function setKeywords($keywords): self {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->metaTags['keywords'] = $keywords;
        return $this;
    }

    /**
     * Set canonical URL
     * 
     * @param string $url Canonical URL
     * @return self
     */
    public function setCanonical(string $url): self {
        $this->metaTags['canonical'] = $url;
        $this->ogTags['og:url'] = $url;
        return $this;
    }

    /**
     * Set Open Graph image
     * 
     * @param string $imageUrl Full URL to image
     * @param int $width Image width (optional)
     * @param int $height Image height (optional)
     * @return self
     */
    public function setOGImage(string $imageUrl, int $width = 0, int $height = 0): self {
        $this->ogTags['og:image'] = $imageUrl;
        if ($width > 0) $this->ogTags['og:image:width'] = (string)$width;
        if ($height > 0) $this->ogTags['og:image:height'] = (string)$height;
        return $this;
    }

    /**
     * Set Open Graph type
     * 
     * @param string $type Type (website, article, etc.)
     * @return self
     */
    public function setOGType(string $type): self {
        $this->ogTags['og:type'] = $type;
        return $this;
    }

    /**
     * Generate Article Schema.org JSON-LD
     * 
     * @param array $post Post data
     * @return self
     */
    public function generateArticleSchema(array $post): self {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post['title'],
            'description' => $post['excerpt'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $post['author_name'] ?? SITE_NAME
            ],
            'datePublished' => date('c', strtotime($post['published_at'] ?? $post['created_at'])),
            'dateModified' => date('c', strtotime($post['updated_at'])),
            'publisher' => [
                '@type' => 'Organization',
                'name' => SITE_NAME,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => SITE_URL . '/assets/images/logo.png'
                ]
            ]
        ];

        if (!empty($post['featured_image'])) {
            $schema['image'] = UPLOAD_URL . '/' . $post['featured_image'];
        }

        if (!empty($post['category_name'])) {
            $schema['articleSection'] = $post['category_name'];
        }

        $this->schemaData[] = $schema;
        return $this;
    }

    /**
     * Generate Person Schema.org JSON-LD (for About page)
     * 
     * @param array $person Person data
     * @return self
     */
    public function generatePersonSchema(array $person): self {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $person['name'] ?? SITE_NAME,
            'jobTitle' => $person['job_title'] ?? SITE_TAGLINE,
            'description' => $person['description'] ?? '',
            'url' => SITE_URL,
            'email' => $person['email'] ?? FROM_EMAIL
        ];

        if (!empty($person['image'])) {
            $schema['image'] = $person['image'];
        }

        $this->schemaData[] = $schema;
        return $this;
    }

    /**
     * Generate Breadcrumb Schema.org JSON-LD
     * 
     * @param array $breadcrumbs Array of ['name' => 'Name', 'url' => 'URL']
     * @return self
     */
    public function generateBreadcrumbSchema(array $breadcrumbs): self {
        $itemList = [];
        $position = 1;

        foreach ($breadcrumbs as $crumb) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'],
                'item' => $crumb['url']
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList
        ];

        $this->schemaData[] = $schema;
        return $this;
    }

    /**
     * Render all meta tags in HTML head
     * 
     * @return string HTML meta tags
     */
    public function renderMetaTags(): string {
        $html = '';

        // Title
        if (isset($this->metaTags['title'])) {
            $html .= "<title>" . htmlspecialchars($this->metaTags['title']) . "</title>\n";
        }

        // Meta description
        if (isset($this->metaTags['description'])) {
            $html .= '<meta name="description" content="' . htmlspecialchars($this->metaTags['description']) . '">' . "\n";
        }

        // Meta keywords
        if (isset($this->metaTags['keywords'])) {
            $html .= '<meta name="keywords" content="' . htmlspecialchars($this->metaTags['keywords']) . '">' . "\n";
        }

        // Canonical URL
        if (isset($this->metaTags['canonical'])) {
            $html .= '<link rel="canonical" href="' . htmlspecialchars($this->metaTags['canonical']) . '">' . "\n";
        }

        // Open Graph tags
        foreach ($this->ogTags as $property => $content) {
            $html .= '<meta property="' . htmlspecialchars($property) . '" content="' . htmlspecialchars($content) . '">' . "\n";
        }

        // Twitter Card tags (using OG data)
        if (isset($this->ogTags['og:title'])) {
            $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
            $html .= '<meta name="twitter:title" content="' . htmlspecialchars($this->ogTags['og:title']) . '">' . "\n";
        }
        if (isset($this->ogTags['og:description'])) {
            $html .= '<meta name="twitter:description" content="' . htmlspecialchars($this->ogTags['og:description']) . '">' . "\n";
        }
        if (isset($this->ogTags['og:image'])) {
            $html .= '<meta name="twitter:image" content="' . htmlspecialchars($this->ogTags['og:image']) . '">' . "\n";
        }

        return $html;
    }

    /**
     * Render Schema.org JSON-LD
     * 
     * @return string Script tag with JSON-LD
     */
    public function renderSchema(): string {
        if (empty($this->schemaData)) {
            return '';
        }

        $json = json_encode($this->schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
    }

    /**
     * Generate XML sitemap
     * 
     * @return string XML sitemap content
     */
    public function generateSitemap(): string {
        $urls = [];

        // Homepage
        $urls[] = [
            'loc' => SITE_URL . '/',
            'changefreq' => 'daily',
            'priority' => '1.0',
            'lastmod' => date('Y-m-d')
        ];

        // Static pages
        $staticPages = ['about', 'writing', 'teaching', 'podcast', 'contact'];
        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => SITE_URL . "/{$page}.php",
                'changefreq' => 'monthly',
                'priority' => '0.8',
                'lastmod' => date('Y-m-d')
            ];
        }

        // Published posts
        $posts = $this->db->fetchAll(
            "SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY published_at DESC"
        );

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => SITE_URL . '/post/' . urlencode($post['slug']),
                'changefreq' => 'weekly',
                'priority' => '0.9',
                'lastmod' => date('Y-m-d', strtotime($post['updated_at']))
            ];
        }

        // Categories
        $categories = $this->db->fetchAll(
            "SELECT slug, updated_at FROM categories"
        );

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => SITE_URL . '/category/' . urlencode($category['slug']),
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'lastmod' => date('Y-m-d', strtotime($category['updated_at']))
            ];
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>";

        return $xml;
    }

    /**
     * Create SEO-friendly slug from text
     * 
     * @param string $text Text to convert
     * @return string URL-safe slug
     */
    public static function createSlug(string $text): string {
        // Vietnamese character mapping
        $vietnamese = [
            'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'đ' => 'd',
            'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        ];

        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        // Replace Vietnamese characters
        $text = strtr($text, $vietnamese);
        
        // Remove special characters
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Replace spaces and multiple hyphens with single hyphen
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        // Trim hyphens from ends
        $text = trim($text, '-');
        
        return $text;
    }
}
