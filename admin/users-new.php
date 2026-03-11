<?php declare(strict_types=1);

/**
 * Create User - Admin
 * 
 * Create a new user account
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$db = Database::getInstance();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'author';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if username or email exists
        $exists = $db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE username = :username OR email = :email",
            ['username' => $username, 'email' => $email]
        );
        
        if ($exists['count'] > 0) {
            $error = 'Username or email already exists';
        } else {
            // Create user
            try {
                $db->insert('users', [
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $fullName,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $success = 'User created successfully';
                
                // Clear form
                $username = $email = $fullName = $password = '';
                
            } catch (Exception $e) {
                error_log('User creation error: ' . $e->getMessage());
                $error = 'Failed to create user';
            }
        }
    }
}

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1>Thêm người dùng mới</h1>
        </div>
        <div class="admin-page__actions">
            <a href="/admin/users.php" class="btn btn-secondary">← Quản lý người dùng</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= escape($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= escape($success) ?></div>
    <?php endif; ?>
    
    <div class="card max-w-2xl mx-auto">
        <form method="POST" action="" class="user-form">
            <div class="form-group">
                <label for="full_name" class="form-label">Họ và tên <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" class="form-input" value="<?= escape($fullName ?? '') ?>" required placeholder="Nhập tên đầy đủ...">
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Tên đăng nhập <span class="required">*</span></label>
                <input type="text" id="username" name="username" class="form-input" value="<?= escape($username ?? '') ?>" required placeholder="username">
                <small class="form-hint">Dùng để đăng nhập. Không được chứa khoảng trắng.</small>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-input" value="<?= escape($email ?? '') ?>" required placeholder="email@vi-du.com">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Mật khẩu <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
                <small class="form-hint">Tối thiểu 6 ký tự.</small>
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Vai trò</label>
                <select name="role" id="role" class="form-select">
                    <option value="author">Tác giả (Chỉ quản lý bài viết của mình)</option>
                    <option value="editor">Biên tập viên (Quản lý tất cả bài viết)</option>
                    <option value="admin">Quản trị viên (Toàn quyền hệ thống)</option>
                </select>
            </div>
            
            <div class="form-actions mt-4 text-right">
                <button type="submit" class="btn btn-primary">Tạo người dùng</button>
            </div>
        </form>
    </div>
</div>

<style>
    .max-w-2xl { max-width: 600px; margin-left: auto; margin-right: auto; }
    .required { color: #ef4444; }
    .text-right { text-align: right; }
    .mt-4 { margin-top: 1.5rem; }
</style>

<script>
// Auto-generate username from full name if empty
document.getElementById('full_name').addEventListener('blur', function() {
    const full = this.value;
    const user = document.getElementById('username');
    if (full && !user.value) {
        // Simple slugify for Vietnamese characters
        let slug = full.toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/[^a-z0-9]/g, '')
            .substring(0, 20);
        user.value = slug;
    }
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
