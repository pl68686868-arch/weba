<?php
// migrate_podcast_schema.php
// Add post_type and spotify_url columns to posts table

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

echo "Starting migration...\n";

$db = Database::getInstance();

try {
    // 1. Add post_type column
    // Check if column exists first to avoid errors on re-run
    $columns = $db->fetchAll("SHOW COLUMNS FROM posts LIKE 'post_type'");
    if (empty($columns)) {
        echo "Adding 'post_type' column...\n";
        $db->query("ALTER TABLE posts ADD COLUMN post_type ENUM('post', 'podcast') DEFAULT 'post' AFTER status");
        echo "✓ 'post_type' column added.\n";
    } else {
        echo "- 'post_type' column already exists.\n";
    }

    // 2. Add spotify_url column
    $columns = $db->fetchAll("SHOW COLUMNS FROM posts LIKE 'spotify_url'");
    if (empty($columns)) {
        echo "Adding 'spotify_url' column...\n";
        $db->query("ALTER TABLE posts ADD COLUMN spotify_url VARCHAR(500) DEFAULT NULL AFTER post_type");
        echo "✓ 'spotify_url' column added.\n";
    } else {
        echo "- 'spotify_url' column already exists.\n";
    }
    
    // 3. Add index for post_type for performance
    $indices = $db->fetchAll("SHOW INDEX FROM posts WHERE Key_name = 'idx_post_type'");
    if (empty($indices)) {
        echo "Adding index for 'post_type'...\n";
        $db->query("ALTER TABLE posts ADD INDEX idx_post_type (post_type)");
        echo "✓ Index 'idx_post_type' added.\n";
    } else {
        echo "- Index 'idx_post_type' already exists.\n";
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
