<?php declare(strict_types=1);

/**
 * Migration Script: Fix Image Paths in Database
 * 
 * This script updates the media and posts tables to store only filenames
 * instead of full paths like "/assets/uploads/filename.jpg"
 * 
 * Run this ONCE after deploying the upload handler fixes.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    // Fix media table
    echo "Updating media table...\n";
    $mediaItems = $db->fetchAll("SELECT id, file_path FROM media WHERE file_path LIKE '/assets/uploads/%'");
    
    foreach ($mediaItems as $item) {
        // Extract filename from full path
        // "/assets/uploads/abc.jpg" -> "abc.jpg"
        $filename = basename($item['file_path']);
        
        $db->update('media', 
            ['file_path' => $filename], 
            'id = :id', 
            ['id' => $item['id']]
        );
    }
    echo "Updated " . count($mediaItems) . " media records\n";
    
    // Fix posts table - featured_image field
    echo "\nUpdating posts table...\n";
    $posts = $db->fetchAll("SELECT id, featured_image FROM posts WHERE featured_image LIKE '/assets/uploads/%'");
    
    foreach ($posts as $post) {
        // Extract filename from full path
        $filename = basename($post['featured_image']);
        
        $db->update('posts', 
            ['featured_image' => $filename], 
            'id = :id', 
            ['id' => $post['id']]
        );
    }
    echo "Updated " . count($posts) . " post records\n";
    
    // Fix site_settings table - image paths
    echo "\nUpdating site_settings table...\n";
    $settings = $db->fetchAll("SELECT id, setting_key, setting_value FROM site_settings WHERE setting_value LIKE '/assets/uploads/%'");
    
    foreach ($settings as $setting) {
        $filename = basename($setting['setting_value']);
        
        $db->update('site_settings', 
            ['setting_value' => $filename], 
            'id = :id', 
            ['id' => $setting['id']]
        );
    }
    echo "Updated " . count($settings) . " setting records\n";
    
    $db->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    echo "Total records updated: " . (count($mediaItems) + count($posts) + count($settings)) . "\n";
    
} catch (Exception $e) {
    $db->rollback();
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
