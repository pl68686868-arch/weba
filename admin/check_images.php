<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<h1>Kiểm tra Ảnh Frontend</h1>";

try {
    // 1. Check constants
    echo "<h2>1. Kiểm tra Constants</h2>";
    echo "<p><strong>UPLOAD_URL:</strong> " . (defined('UPLOAD_URL') ? UPLOAD_URL : '<span style="color:red">KHÔNG TỒN TẠI</span>') . "</p>";
    echo "<p><strong>ASSETS_URL:</strong> " . (defined('ASSETS_URL') ? ASSETS_URL : '<span style="color:red">KHÔNG TỒN TẠI</span>') . "</p>";
    
    // 2. Check database settings
    echo "<h2>2. Kiểm tra Database Settings</h2>";
    $imageKeys = ['about_hero_image', 'teaching_hero_image', 'podcast_cover_art', 'contact_hero_image'];
    foreach ($imageKeys as $key) {
        $value = get_setting($key, '');
        if ($value) {
            echo "<p>✅ <strong>{$key}:</strong> {$value}</p>";
            
            // Check if file exists
            $filePath = __DIR__ . '/../assets/uploads/' . $value;
            if (file_exists($filePath)) {
                echo "<p style='margin-left:2em; color:green'>✅ File tồn tại: {$filePath}</p>";
                echo "<p style='margin-left:2em'>Size: " . filesize($filePath) . " bytes</p>";
            } else {
                echo "<p style='margin-left:2em; color:red'>❌ File KHÔNG tồn tại: {$filePath}</p>";
            }
            
            // Show expected URL
            if (defined('UPLOAD_URL')) {
                $expectedUrl = UPLOAD_URL . '/' . $value;
                echo "<p style='margin-left:2em'>URL dự kiến: <a href='{$expectedUrl}' target='_blank'>{$expectedUrl}</a></p>";
            }
        } else {
            echo "<p>⚪ <strong>{$key}:</strong> <em>Chưa có ảnh</em></p>";
        }
    }
    
    // 3. Check uploads directory
    echo "<h2>3. Kiểm tra Thư mục Uploads</h2>";
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (is_dir($uploadDir)) {
        echo "<p style='color:green'>✅ Thư mục tồn tại: {$uploadDir}</p>";
        $files = scandir($uploadDir);
        $imageFiles = array_filter($files, function($file) use ($uploadDir) {
            return is_file($uploadDir . $file) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
        });
        
        if (count($imageFiles) > 0) {
            echo "<p>Có " . count($imageFiles) . " file ảnh:</p>";
            echo "<ul>";
            foreach ($imageFiles as $file) {
                echo "<li>{$file}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>⚠️ Thư mục trống (không có ảnh).</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Thư mục KHÔNG tồn tại: {$uploadDir}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
