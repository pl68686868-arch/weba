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
        <h1>Edit User: <?= escape($user['username']) ?></h1>
        <a href="/admin/users.php" class="btn btn-secondary">← Back to Users</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= escape($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= escape($success) ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-input" value="<?= escape($user['username']) ?>" disabled>
                <small class="form-hint">Username cannot be changed.</small>
            </div>
            
            <div class="form-group">
                <label for="full_name" class="form-label">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-input" value="<?= escape($user['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email *</label>
                <input type="email" id="email" name="email" class="form-input" value="<?= escape($user['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">New Password (leave empty to keep current)</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••">
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-select" <?= ($userId === $currentUserId) ? 'disabled' : '' ?>>
                    <option value="author" <?= $user['role'] === 'author' ? 'selected' : '' ?>>Author</option>
                    <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
                <?php if ($userId === $currentUserId): ?>
                    <input type="hidden" name="role" value="<?= $user['role'] ?>">
                    <small class="form-hint">You cannot change your own role.</small>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
