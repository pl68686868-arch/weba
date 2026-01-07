<?php declare(strict_types=1);

/**
 * Cache class for multi-layer caching system
 * 
 * Features:
 * - Page cache (full HTML output)
 * - Object cache (query results, data)
 * - Fragment cache (widgets, components)
 * - File-based and database-based caching
 * - Cache invalidation
 * 
 * @package Weba
 * @author Danny Duong
 */
class Cache {
    private Database $db;
    private string $cacheDir;
    private int $defaultLifetime;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->cacheDir = CACHE_PATH;
        $this->defaultLifetime = CACHE_LIFETIME;

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached item
     * 
     * @param string $key Cache key
     * @param bool $useFileCache Use file cache instead of database
     * @return mixed|false Cached data or false if not found/expired
     */
    public function get(string $key, bool $useFileCache = false) {
        if (!CACHE_ENABLED) {
            return false;
        }

        if ($useFileCache) {
            return $this->getFromFile($key);
        } else {
            return $this->getFromDatabase($key);
        }
    }

    /**
     * Set cached item
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $lifetime Lifetime in seconds (0 for default)
     * @param bool $useFileCache Use file cache instead of database
     * @return bool Success
     */
    public function set(string $key, $value, int $lifetime = 0, bool $useFileCache = false): bool {
        if (!CACHE_ENABLED) {
            return false;
        }

        $lifetime = $lifetime > 0 ? $lifetime : $this->defaultLifetime;

        if ($useFileCache) {
            return $this->setToFile($key, $value, $lifetime);
        } else {
            return $this->setToDatabase($key, $value, $lifetime);
        }
    }

    /**
     * Delete cached item
     * 
     * @param string $key Cache key
     * @param bool $useFileCache Use file cache
     * @return bool Success
     */
    public function delete(string $key, bool $useFileCache = false): bool {
        if ($useFileCache) {
            $filename = $this->getCacheFilename($key);
            if (file_exists($filename)) {
                return unlink($filename);
            }
            return false;
        } else {
            return $this->db->delete('cache', 'cache_key = :key', ['key' => $key]) > 0;
        }
    }

    /**
     * Clear all cache
     * 
     * @return bool Success
     */
    public function clearAll(): bool {
        // Clear database cache
        try {
            $this->db->query("TRUNCATE TABLE cache");
        } catch (Exception $e) {
            error_log('Clear cache error: ' . $e->getMessage());
        }

        // Clear file cache
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Clear expired cache entries
     * 
     * @return bool Success
     */
    public function clearExpired(): bool {
        // Clear expired database cache
        try {
            $this->db->query("DELETE FROM cache WHERE expires_at < NOW()");
        } catch (Exception $e) {
            error_log('Clear expired cache error: ' . $e->getMessage());
        }

        // Clear expired file cache
        $files = glob($this->cacheDir . '/*.cache');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $now) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Cache a page output
     * 
     * @param string $url URL of the page
     * @param string $content HTML content
     * @param int $lifetime Cache lifetime
     * @return bool Success
     */
    public function cachePage(string $url, string $content, int $lifetime = 0): bool {
        $key = 'page_' . md5($url);
        return $this->set($key, $content, $lifetime, true);
    }

    /**
     * Get cached page
     * 
     * @param string $url URL of the page
     * @return string|false HTML content or false
     */
    public function getCachedPage(string $url) {
        $key = 'page_' . md5($url);
        return $this->get($key, true);
    }

    /**
     * Start output buffering for page caching
     * 
     * @param string $url Current URL
     * @param int $lifetime Cache lifetime
     * @return bool True if cached page was served, false if need to generate
     */
    public function startPageCache(string $url, int $lifetime = 0): bool {
        $cached = $this->getCachedPage($url);
        
        if ($cached !== false) {
            echo $cached;
            return true;
        }

        // Start output buffering
        ob_start(function($content) use ($url, $lifetime) {
            $this->cachePage($url, $content, $lifetime);
            return $content;
        });

        return false;
    }

    /**
     * End page cache output buffering
     * 
     * @return void
     */
    public function endPageCache(): void {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Remember callback result (cache or execute)
     * 
     * @param string $key Cache key
     * @param callable $callback Function to execute if not cached
     * @param int $lifetime Cache lifetime
     * @return mixed Result from cache or callback
     */
    public function remember(string $key, callable $callback, int $lifetime = 0) {
        $cached = $this->get($key);
        
        if ($cached !== false) {
            return $cached;
        }

        $result = $callback();
        $this->set($key, $result, $lifetime);
        
        return $result;
    }

    /**
     * Get from file cache
     * 
     * @param string $key Cache key
     * @return mixed|false
     */
    private function getFromFile(string $key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }

        $data = @unserialize(file_get_contents($filename));
        
        if ($data === false) {
            return false;
        }

        // Check expiration
        if ($data['expires_at'] < time()) {
            unlink($filename);
            return false;
        }

        return $data['value'];
    }

    /**
     * Set to file cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value
     * @param int $lifetime Lifetime in seconds
     * @return bool Success
     */
    private function setToFile(string $key, $value, int $lifetime): bool {
        $filename = $this->getCacheFilename($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $lifetime
        ];

        $serialized = serialize($data);
        
        return file_put_contents($filename, $serialized, LOCK_EX) !== false;
    }

    /**
     * Get from database cache
     * 
     * @param string $key Cache key
     * @return mixed|false
     */
    private function getFromDatabase(string $key) {
        try {
            $sql = "SELECT cache_value, expires_at 
                    FROM cache 
                    WHERE cache_key = :key 
                    AND expires_at > NOW() 
                    LIMIT 1";
            
            $row = $this->db->fetchOne($sql, ['key' => $key]);
            
            if (!$row) {
                return false;
            }

            return unserialize($row['cache_value']);

        } catch (Exception $e) {
            error_log('Get cache error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set to database cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value
     * @param int $lifetime Lifetime in seconds
     * @return bool Success
     */
    private function setToDatabase(string $key, $value, int $lifetime): bool {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $lifetime);
            $serialized = serialize($value);

            // Check if key exists
            if ($this->db->exists('cache', 'cache_key = :key', ['key' => $key])) {
                // Update existing
                return $this->db->update(
                    'cache',
                    [
                        'cache_value' => $serialized,
                        'expires_at' => $expiresAt
                    ],
                    'cache_key = :key',
                    ['key' => $key]
                ) > 0;
            } else {
                // Insert new
                return $this->db->insert('cache', [
                    'cache_key' => $key,
                    'cache_value' => $serialized,
                    'expires_at' => $expiresAt
                ]) > 0;
            }

        } catch (Exception $e) {
            error_log('Set cache error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache filename for a key
     * 
     * @param string $key Cache key
     * @return string Full path to cache file
     */
    private function getCacheFilename(string $key): string {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
}
