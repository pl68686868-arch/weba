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
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->getUserRole() !== 'admin') {
    die('Unauthorized access');
}

// Add admin header
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="card mb-4" style="padding: 24px;">
    <h1>Database Migration: Fix Image Paths</h1>
    <p class="text-muted">Converting absolute paths (e.g., /assets/uploads/image.jpg) to filenames (image.jpg).</p>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; max-height: 500px; overflow-y: auto;">
<?php
$db = Database::getInstance();
flush(); // Flush buffer to show header

try {
    $db->beginTransaction();
    echo "Starting migration process...\n\n";
    
    // Fix media table
    echo "1. Checking 'media' table...\n";
    $mediaItems = $db->fetchAll("SELECT id, file_path FROM media WHERE file_path LIKE '/assets/uploads/%'");
    
    if (empty($mediaItems)) {
        echo "   No records found needing update.\n";
    } else {
        foreach ($mediaItems as $item) {
            $filename = basename($item['file_path']);
            $db->update('media', ['file_path' => $filename], 'id = :id', ['id' => $item['id']]);
        }
        echo "   ✅ Updated " . count($mediaItems) . " media records\n";
    }
    
    // Fix posts table
    echo "\n2. Checking 'posts' table (featured_image)...\n";
    $posts = $db->fetchAll("SELECT id, featured_image FROM posts WHERE featured_image LIKE '/assets/uploads/%'");
    
    if (empty($posts)) {
        echo "   No records found needing update.\n";
    } else {
        foreach ($posts as $post) {
            $filename = basename($post['featured_image']);
            $db->update('posts', ['featured_image' => $filename], 'id = :id', ['id' => $post['id']]);
        }
        echo "   ✅ Updated " . count($posts) . " post records\n";
    }
    
    // Fix site_settings table
    echo "\n3. Checking 'site_settings' table...\n";
    $settings = $db->fetchAll("SELECT id, setting_key, setting_value FROM site_settings WHERE setting_value LIKE '/assets/uploads/%'");
    
    if (empty($settings)) {
        echo "   No records found needing update.\n";
    } else {
        foreach ($settings as $setting) {
            $filename = basename($setting['setting_value']);
            $db->update('site_settings', ['setting_value' => $filename], 'id = :id', ['id' => $setting['id']]);
        }
        echo "   ✅ Updated " . count($settings) . " setting records\n";
    }
    
    $db->commit();
    echo "\n🎉 MIGRATION COMPLETED SUCCESSFULLY!";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    echo "\n❌ ERROR: Migration failed!\n";
    echo "Reason: " . $e->getMessage();
}
?>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="/admin/dashboard.php" class="btn btn-primary">Return to Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
