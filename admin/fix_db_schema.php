<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

echo "<h1>Kiểm tra và Sửa lỗi Database</h1>";

try {
    $db = Database::getInstance();
    
    // Check if table exists
    try {
        $db->fetchAll("SELECT 1 FROM site_settings LIMIT 1");
        echo "<p style='color:green'>✅ Bảng <code>site_settings</code> ĐÃ tồn tại.</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠️ Bảng <code>site_settings</code> CHƯA tồn tại. Đang tạo...</p>";
        
        $sql = "CREATE TABLE IF NOT EXISTS site_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
            description VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->query($sql);
        echo "<p style='color:green'>✅ Đã tạo bảng <code>site_settings</code> thành công.</p>";
    }

    // Check uploads directory
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "<p style='color:green'>✅ Đã tạo thư mục <code>assets/uploads/</code>.</p>";
        } else {
            echo "<p style='color:red'>❌ Không thể tạo thư mục <code>assets/uploads/</code>. Vui lòng tạo thủ công và set quyền 755/777.</p>";
        }
    } else {
        if (is_writable($uploadDir)) {
             echo "<p style='color:green'>✅ Thư mục <code>assets/uploads/</code> tồn tại và có quyền ghi.</p>";
        } else {
             echo "<p style='color:red'>❌ Thư mục <code>assets/uploads/</code> tồn tại nhưng KHÔNG có quyền ghi. Vui lòng set quyền 755/777.</p>";
        }
    }

    echo "<hr><p>Vui lòng thử lại trang <a href='/admin/appearance.php'>Giao diện</a>.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
