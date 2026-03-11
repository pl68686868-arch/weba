<?php declare(strict_types=1);

/**
 * Edit User - Admin
 * 
 * Update user account details
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
$userId = (int)($_GET['id'] ?? 0);
$currentUserId = $auth->getUserId();

// Fetch user
$user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);

if (!$user) {
    header('Location: /admin/users.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'author';
    
    // Validation
    if (empty($email) || empty($fullName)) {
        $error = 'Name and Email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Check email duplications (excluding self)
        $exists = $db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE email = :email AND id != :id",
            ['email' => $email, 'id' => $userId]
        );
        
        if ($exists['count'] > 0) {
            $error = 'Email already in use by another user';
        } else {
            // Prevent removing self admin check
            if ($userId === $currentUserId && $role !== 'admin') {
                $error = 'You cannot remove your own admin status';
            } else {
                try {
                    $updateData = [
                        'email' => $email,
                        'full_name' => $fullName,
                        'role' => $role
                    ];
                    
                    $setClauses = [];
                    foreach ($updateData as $key => $value) {
                        $setClauses[] = "$key = :$key";
                    }
                    
                    // Update password if provided
                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                            throw new Exception("Password must be at least 6 characters");
                        }
                        $setClauses[] = "password_hash = :password_hash";
                        $updateData['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    // Add ID to params
                    $updateData['id'] = $userId;
                    $setSql = implode(', ', $setClauses);
                    
                    $db->query("UPDATE users SET $setSql WHERE id = :id", $updateData);
                    $success = 'User updated successfully';
                    
                    // Refresh user data
                    $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
                    
                } catch (Exception $e) {
                    error_log('User update error: ' . $e->getMessage());
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <h1>Chỉnh sửa người dùng: <?= escape($user['username']) ?></h1>
        <a href="/admin/users.php" class="btn btn-secondary">← Quản lý người dùng</a>
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
                <label class="form-label">Tên đăng nhập</label>
                <input type="text" class="form-input" value="<?= escape($user['username']) ?>" disabled>
                <small class="form-hint">Tên đăng nhập không thể thay đổi.</small>
            </div>
            
            <div class="form-group">
                <label for="full_name" class="form-label">Họ và tên <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" class="form-input" value="<?= escape($user['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-input" value="<?= escape($user['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••">
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Vai trò</label>
                <select name="role" id="role" class="form-select" <?= ($userId === $currentUserId) ? 'disabled' : '' ?>>
                    <option value="author" <?= $user['role'] === 'author' ? 'selected' : '' ?>>Tác giả (Chỉ quản lý bài viết của mình)</option>
                    <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Biên tập viên (Quản lý tất cả bài viết)</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên (Toàn quyền hệ thống)</option>
                </select>
                <?php if ($userId === $currentUserId): ?>
                    <input type="hidden" name="role" value="<?= $user['role'] ?>">
                    <small class="form-hint">Bạn không thể tự thay đổi vai trò của mình.</small>
                <?php endif; ?>
            </div>
            
            <div class="form-actions mt-4 text-right">
                <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
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

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
