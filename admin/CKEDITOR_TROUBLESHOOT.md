# CKEditor Troubleshooting Guide

## Current Issue
CKEditor không hiển thị trên trang (chỉ thấy textarea thuần).

## Diagnostic Steps

### Bước 1: Test CKEditor riêng biệt
Upload file `test_ckeditor.html` lên server và truy cập:
```
duongtranminhdoan.com/admin/test_ckeditor.html
```

Kiểm tra:
- ✅ Nếu thấy thanh công cụ CKEditor → **CDN hoạt động tốt**
- ❌ Nếu vẫn chỉ thấy textarea → **CDN bị chặn hoặc lỗi mạng**

### Bước 2: Kiểm tra Console
Mở Developer Tools (F12) → Tab Console
- Tìm lỗi màu đỏ liên quan đến CKEditor
- Lỗi thường gặp:
  - `net::ERR_BLOCKED_BY_CLIENT` → Bị AdBlock chặn
  - `Failed to load resource` → CDN không load được
  - `CKEDITOR is not defined` → Script chưa load

### Bước 3: Verify file version trên server
Truy cập: `duongtranminhdoan.com/admin/check_ckeditor_version.php`
- ✅ Màu xanh (4.25.1-lts) → Version đúng
- ❌ Màu đỏ (4.22.1) → Cần upload lại file

## Common Fixes

### Fix 1: Upload lại files (Nếu version sai)
Upload 3 files sau lên `/home/iwzwumvq/public_html/admin/`:
- `posts-new.php`
- `posts-edit.php`  
- `check_ckeditor_version.php`

### Fix 2: Hard Refresh
- Windows: `Ctrl + F5`
- Mac: `Cmd + Shift + R`

### Fix 3: Disable AdBlock
Tắt AdBlock/uBlock cho website này và reload

### Fix 4: Kiểm tra HTTPS
Nếu CDN dùng HTTPS mà site dùng HTTP → Mixed content error
→ Cần force HTTPS cho toàn site

## Expected Result
Sau khi fix, bạn sẽ thấy:
- Thanh công cụ CKEditor đầy đủ
- Các nút Bold, Italic, Font, Color...
- Không còn security warning màu đỏ
