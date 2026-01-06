# Hướng Dẫn Deploy Website Lên Azdigi Hosting

🌿 **Triển khai website Psychology & Mindfulness lên Azdigi cPanel**

---

## 📋 **Chuẩn Bị**

✅ Code đã upload lên `/home/wzvxumvq/repositories/weba` (như trong screenshot)  
✅ Có quyền truy cập cPanel (đã có)  
✅ Domain: `duongtranminhdoan.com` hoặc subdomain

---

## 🗄️ **BƯỚC 1: Tạo Database MySQL**

### 1.1. Vào MySQL Databases trong cPanel
1. Từ cPanel Dashboard, tìm mục **"Databases"**
2. Click **"MySQL Databases"**

### 1.2. Tạo Database Mới
```
Database Name: wzvxumvq_weba
```
- cPanel sẽ tự động thêm prefix `wzvxumvq_`
- Database đầy đủ sẽ là: `wzvxumvq_weba`

### 1.3. Tạo MySQL User
```
Username: wzvxumvq_admin
Password: [Tạo password mạnh - lưu lại]
```

**Ví dụ password mạnh:**
```
W3b@2024$ecur3!Ps
```

### 1.4. Add User To Database
1. Chọn User: `wzvxumvq_admin`
2. Chọn Database: `wzvxumvq_weba`
3. Click **"Add"**
4. Chọn **"ALL PRIVILEGES"**
5. Click **"Make Changes"**

---

## 📤 **BƯỚC 2: Import Database Schema**

### 2.1. Vào phpMyAdmin
1. Trong cPanel, tìm **"Databases"** → **"phpMyAdmin"**
2. Click vào database `wzvxumvq_weba` bên trái

### 2.2. Import File SQL
1. Click tab **"Import"** ở trên
2. Click **"Choose File"**
3. Chọn file: `/home/wzvxumvq/repositories/weba/database/schema.sql`
4. Click **"Go"** ở cuối trang

**Kết quả:** Sẽ có 25+ tables được tạo (users, posts, categories, tags, etc.)

### 2.3. Kiểm Tra
1. Click vào table **"users"** bên trái
2. Click tab **"Browse"**
3. Phải thấy 1 dòng với username `admin`

---

## ⚙️ **BƯỚC 3: Cấu Hình File config.php**

### 3.1. Tạo File Config
1. Trong File Manager, vào thư mục `/home/wzvxumvq/repositories/weba/config/`
2. Click **"+ File"** để tạo file mới
3. Tên file: `config.php`

### 3.2. Nội Dung File Config

Copy đoạn code sau vào `config.php`:

```php
<?php
declare(strict_types=1);

/**
 * Configuration File for Production
 * 
 * @package Weba
 * @author Danny Duong
 */

// ============================================================================
// DATABASE CONFIGURATION
// ============================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'wzvxumvq_weba');
define('DB_USER', 'wzvxumvq_admin');
define('DB_PASS', 'YOUR_PASSWORD_HERE'); // Thay bằng password bạn tạo ở Bước 1.3
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// SITE CONFIGURATION
// ============================================================================
define('SITE_NAME', 'Dương Trần Minh Đoan');
define('SITE_TAGLINE', 'Giảng viên, người thực hành tâm lý và chánh niệm');
define('SITE_URL', 'https://duongtranminhdoan.com'); // Đổi thành domain của bạn
define('ADMIN_EMAIL', 'pl68686868@gmail.com');

// ============================================================================
// PATHS
// ============================================================================
define('BASE_PATH', '/home/wzvxumvq/repositories/weba');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('CACHE_PATH', BASE_PATH . '/cache');
define('LOG_PATH', BASE_PATH . '/logs');

define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOAD_URL', SITE_URL . '/uploads');

// ============================================================================
// SECURITY
// ============================================================================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 7200); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutes

// ============================================================================
// FILE UPLOAD
// ============================================================================
define('MAX_IMAGE_SIZE', 5242880); // 5MB
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// ============================================================================
// CACHING
// ============================================================================
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour
define('PAGE_CACHE_LIFETIME', 300); // 5 minutes

// ============================================================================
// PAGINATION
// ============================================================================
define('POSTS_PER_PAGE', 10);

// ============================================================================
// EMAIL CONFIGURATION
// ============================================================================
define('SMTP_ENABLED', false); // Set to true when configuring email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@duongtranminhdoan.com');
define('FROM_NAME', SITE_NAME);

// ============================================================================
// SEO
// ============================================================================
define('DEFAULT_META_DESCRIPTION', 'Không gian chia sẻ về tâm lý học, chánh niệm và phát triển con người');
define('DEFAULT_META_KEYWORDS', 'tâm lý học, chánh niệm, mindfulness, giáo dục người lớn');
define('DEFAULT_OG_IMAGE', ASSETS_URL . '/images/og-default.jpg');

// ============================================================================
// GOOGLE ANALYTICS
// ============================================================================
define('GA_TRACKING_ID', ''); // Add your GA4 ID

// ============================================================================
// ENVIRONMENT
// ============================================================================
define('ENVIRONMENT', 'production');

// Error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php-errors.log');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1'); // Requires HTTPS
ini_set('session.use_strict_mode', '1');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-create required directories
$dirs = [UPLOAD_PATH, CACHE_PATH, LOG_PATH];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
```

### 3.3. Thay Đổi Quan Trọng

1. **Dòng 16**: Thay `YOUR_PASSWORD_HERE` bằng password MySQL bạn tạo
2. **Dòng 24**: Thay `duongtranminhdoan.com` bằng domain thực tế
3. **Dòng 25**: Thay email admin

### 3.4. Lưu File
1. Click **"Save Changes"**
2. Set permissions: **0644**

---

## 🌐 **BƯỚC 4: Cấu Hình Domain**

### Option A: Dùng Domain Chính (duongtranminhdoan.com)

1. **Di chuyển files:**
   ```
   Từ: /home/wzvxumvq/repositories/weba/*
   Đến: /home/wzvxumvq/public_html/
   ```

2. **Trong File Manager:**
   - Select tất cả files trong `/repositories/weba/`
   - Click **"Move"**
   - Destination: `/home/wzvxumvq/public_html/`

### Option B: Dùng Subdomain (weba.duongtranminhdoan.com)

1. **Tạo Subdomain trong cPanel:**
   - Vào **"Domains"** → **"Subdomains"**
   - Subdomain: `weba`
   - Document Root: `/home/wzvxumvq/repositories/weba`
   - Click **"Create"**

2. **Update config.php:**
   ```php
   define('SITE_URL', 'https://weba.duongtranminhdoan.com');
   ```

---

## 🔒 **BƯỚC 5: Bảo Mật & Hoàn Thiện**

### 5.1. Set File Permissions
Trong File Manager:

```
Directories: 0755
Files: 0644
config/config.php: 0640
uploads/: 0755
cache/: 0755
logs/: 0755
```

### 5.2. Xóa File Setup
1. Xóa file `/setup.php` (nếu có)

### 5.3. Enable SSL Certificate
1. Trong cPanel → **"Security"** → **"SSL/TLS Status"**
2. Click **"Run AutoSSL"** cho domain
3. Chờ vài phút để certificate được cài

### 5.4. Force HTTPS
File `.htaccess` đã có sẵn, chỉ cần uncomment dòng 15:

```apache
# Uncomment to force HTTPS (line 15 in .htaccess)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## ✅ **BƯỚC 6: Test Website**

### 6.1. Truy Cập Frontend
```
https://duongtranminhdoan.com
```

**Kiểm tra:**
- ✅ Homepage hiển thị
- ✅ Không có lỗi 500
- ✅ CSS/JS load được

### 6.2. Truy Cập Admin
```
https://duongtranminhdoan.com/admin/login.php
```

**Login với:**
```
Username: admin
Password: admin123
```

⚠️ **QUAN TRỌNG**: Đổi password ngay sau khi login lần đầu!

### 6.3. Kiểm Tra Database Connection
Nếu thấy lỗi "Database connection failed":
1. Kiểm tra lại `config.php` (DB_HOST, DB_NAME, DB_USER, DB_PASS)
2. Kiểm tra MySQL user có quyền ALL PRIVILEGES
3. Xem PHP error log trong `/home/wzvxumvq/repositories/weba/logs/`

---

## 🐛 **Troubleshooting**

### Lỗi 500 Internal Server Error
**Nguyên nhân:** Thường do .htaccess hoặc PHP errors

**Giải quyết:**
1. Tạm thời rename `.htaccess` → `.htaccess.bak`
2. Test lại
3. Nếu OK, vấn đề là `.htaccess`
4. Check PHP version: Cần PHP 8.0+

### Lỗi "Database connection failed"
**Check list:**
- ✅ Database name đúng (có prefix `wzvxumvq_`)
- ✅ Username đúng (có prefix `wzvxumvq_`)
- ✅ Password đúng
- ✅ User đã được add vào database với ALL PRIVILEGES

### Lỗi "Permission denied" khi upload
**Giải quyết:**
```bash
# Set permissions cho uploads folder
chmod 755 uploads/
```

### CSS/JS không load
**Kiểm tra:**
1. SITE_URL trong `config.php` đúng domain
2. ASSETS_URL đúng
3. Files trong `/assets/` có permissions 0644

---

## 📝 **Checklist Deployment**

- [ ] Database created (`wzvxumvq_weba`)
- [ ] MySQL user created và granted permissions
- [ ] Schema.sql imported (25+ tables)
- [ ] config.php created với đúng credentials
- [ ] Domain/subdomain configured
- [ ] File permissions set correctly (755/644)
- [ ] SSL certificate installed
- [ ] HTTPS redirect enabled
- [ ] setup.php deleted
- [ ] Test frontend (homepage loads)
- [ ] Test admin login
- [ ] Admin password changed from default

---

## 🎯 **Next Steps Sau Khi Deploy**

1. **Đổi Admin Password:**
   - Login admin panel
   - Vào Settings hoặc dùng phpMyAdmin
   - Update password với bcrypt hash

2. **Tạo Bài Viết Đầu Tiên:**
   - Vào Admin → Posts
   - Dùng phpMyAdmin để insert hoặc tạo post editor

3. **Configure Email:**
   - Update SMTP settings trong `config.php`
   - Test newsletter subscription

4. **Upload Logo/Images:**
   - Tạo thư mục `assets/images/`
   - Upload logo, OG image
   - Update DEFAULT_OG_IMAGE

5. **Setup Backup:**
   - cPanel → Backup Wizard
   - Schedule automatic backups

---

## 📞 **Support**

Nếu gặp vấn đề:
1. Check error logs: `/home/wzvxumvq/repositories/weba/logs/php-errors.log`
2. Check Apache error log trong cPanel → Errors
3. Test database connection trong phpMyAdmin

**Common Hosting Paths:**
```
Home:        /home/wzvxumvq/
Public HTML: /home/wzvxumvq/public_html/
Weba:        /home/wzvxumvq/repositories/weba/
```

---

**Chúc bạn deploy thành công! 🚀**
