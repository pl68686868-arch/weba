<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<h1>Test Helper Functions</h1>";

try {
    // Test if functions exist
    if (function_exists('get_setting')) {
        echo "<p style='color:green'>✅ Hàm <code>get_setting()</code> ĐÃ tồn tại.</p>";
        
        // Test get_setting
        $testValue = get_setting('site_name', 'default');
        echo "<p>Test get_setting('site_name'): <strong>" . htmlspecialchars($testValue) . "</strong></p>";
    } else {
        echo "<p style='color:red'>❌ Hàm <code>get_setting()</code> CHƯA tồn tại trong functions.php.</p>";
        echo "<p>👉 Anh cần upload lại file <code>/includes/functions.php</code></p>";
    }
    
    if (function_exists('set_setting')) {
        echo "<p style='color:green'>✅ Hàm <code>set_setting()</code> ĐÃ tồn tại.</p>";
    } else {
        echo "<p style='color:red'>❌ Hàm <code>set_setting()</code> CHƯA tồn tại trong functions.php.</p>";
        echo "<p>👉 Anh cần upload lại file <code>/includes/functions.php</code></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}

echo "<hr><p>Nếu tất cả đều xanh ✅, thử lại <a href='/admin/appearance.php'>trang Giao diện</a>.</p>";
