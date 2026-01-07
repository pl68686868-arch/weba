# Fix: Website Hiển Thị Trang Default Azdigi

## 🔍 **Vấn Đề**
Website đang hiển thị trang "CHÀO MỪNG ĐẾN VỚI AZDIGI" thay vì code của bạn.

---

## 🎯 **Nguyên Nhân & Giải Pháp**

### **Nguyên nhân 1: Files ở sai vị trí**

**Kiểm tra:**
1. Vào **File Manager** trong cPanel
2. Check đường dẫn domain của bạn trỏ đến đâu

**Cách 1: Nếu dùng domain chính (duongtranminhdoan.com)**

Domain chính phải trỏ đến `/home/wzvxumvq/public_html/`

**GIẢI PHÁP:**
```bash
# Di chuyển TẤT CẢ files từ repositories/weba vào public_html

Trong File Manager:
1. Vào /home/wzvxumvq/repositories/weba/
2. Select ALL files (Ctrl+A hoặc click checkbox đầu tiên)
3. Click "Move"
4. Destination: /home/wzvxumvq/public_html/
5. Click "Move File(s)"
```

**QUAN TRỌNG:** Di chuyển files, KHÔNG copy. Sau khi di chuyển:
- `/public_html/` phải có: index.php, about.php, admin/, includes/, etc.
- KHÔNG phải `/public_html/weba/index.php` ← SAI

---

**Cách 2: Nếu dùng subdomain (TEST.duongtranminhdoan.com)**

**GIẢI PHÁP:**
1. Trong cPanel → **"Domains"** → **"Subdomains"**
2. Tạo subdomain mới:
   - Subdomain: `test` (hoặc tên khác)
   - Document Root: `/home/wzvxumvq/repositories/weba`
3. Click **"Create"**
4. Truy cập: `https://test.duongtranminhdoan.com`

---

### **Nguyên nhân 2: File index.php bị thiếu/lỗi**

**Kiểm tra:**
1. Trong File Manager, vào thư mục chứa website
2. Phải có file `index.php` (KHÔNG phải index.html)
3. Click chuột phải `index.php` → **"Edit"**
4. Kiểm tra nội dung phải là PHP code (bắt đầu bằng `<?php`)

**Nếu không có index.php:**
- Download lại từ GitHub
- Hoặc tạo mới với nội dung từ repository

---

### **Nguyên nhân 3: .htaccess có vấn đề**

**Kiểm tra:**
1. Trong File Manager, bật **"Show Hidden Files"** (Settings icon góc trên phải)
2. Phải có file `.htaccess`
3. File permissions phải là **0644**

**Nếu .htaccess bị lỗi:**
```apache
# Tạm thời rename để test
.htaccess → .htaccess.bak

# Nếu website chạy được sau khi rename
# → Vấn đề là .htaccess
# → Kiểm tra lại nội dung file
```

---

### **Nguyên nhân 4: PHP Version sai**

**Kiểm tra PHP Version:**
1. Trong cPanel → **"Software"** → **"Select PHP Version"**
2. Phải chọn **PHP 8.0** hoặc **PHP 8.1** trở lên
3. Nếu đang dùng PHP 7.x → Đổi lên PHP 8.x

---

## 📝 **CHECKLIST DEBUG**

Làm theo thứ tự:

### ✅ Bước 1: Kiểm tra vị trí files
```
Vào File Manager
→ Confirm domain trỏ đến thư mục nào?
→ Thư mục đó có file index.php không?
```

**Domain chính:** `/home/wzvxumvq/public_html/index.php` phải tồn tại  
**Subdomain test:** `/home/wzvxumvq/repositories/weba/index.php` phải tồn tại

### ✅ Bước 2: Test trực tiếp file PHP
```
Thử truy cập: http://IP-cua-ban/~wzvxumvq/index.php
Hoặc: http://domain.com/index.php
```

**Nếu thấy PHP code:** → .htaccess redirect có vấn đề  
**Nếu vẫn thấy trang default:** → File không đúng vị trí

### ✅ Bước 3: Kiểm tra permissions
```
File Manager → Click vào file index.php
→ Permissions → Phải là 0644 (rw-r--r--)
```

### ✅ Bước 4: Xem error log
```
cPanel → Metrics → Errors
→ Click vào "Error Log" cuối cùng
→ Tìm lỗi PHP
```

---

## 🚀 **GIẢI PHÁP NHANH NHẤT**

### **Nếu bạn muốn test ngay:**

**Tạo subdomain mới:**
1. cPanel → Domains → Subdomains
2. Subdomain: `test`
3. Document Root: `/home/wzvxumvq/repositories/weba`
4. Create
5. Truy cập: `https://test.duongtranminhdoan.com`

**Ưu điểm:**
- Không cần di chuyển files
- Giữ nguyên public_html (nếu có gì đó)
- Test được ngay

---

## 🔧 **GIẢI PHÁP ĐỀ XUẤT CHO PRODUCTION**

**Di chuyển files vào public_html:**

```bash
# Trong File Manager:

1. Vào /home/wzvxumvq/public_html/
2. DELETE hoặc RENAME file index.html hiện tại (nếu có)
   index.html → index.html.old

3. Vào /home/wzvxumvq/repositories/weba/
4. Select ALL files
5. Move to: /home/wzvxumvq/public_html/

6. Sau khi move, public_html phải có:
   - index.php
   - about.php
   - admin/
   - api/
   - assets/
   - includes/
   - config/
   - .htaccess
   - etc.
```

**Sau đó truy cập:**
```
https://duongtranminhdoan.com
```

---

## 📞 **Nếu vẫn không được**

Gửi cho tôi screenshots:
1. File Manager trong `/public_html/` (hoặc thư mục domain trỏ đến)
2. List các files có trong đó
3. PHP Version trong Select PHP Version
4. Error log cuối cùng

---

**Bạn muốn dùng cách nào?**
- [ ] **Cách 1**: Di chuyển files vào public_html (cho domain chính)
- [ ] **Cách 2**: Tạo subdomain test (test nhanh, không ảnh hưởng gì)
