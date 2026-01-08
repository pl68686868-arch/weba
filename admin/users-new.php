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
        <h1>Create New User</h1>
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
                <label for="full_name" class="form-label">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-input" value="<?= escape($fullName ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Username *</label>
                <input type="text" id="username" name="username" class="form-input" value="<?= escape($username ?? '') ?>" required>
                <small class="form-hint">Used for login. No spaces allowed.</small>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email *</label>
                <input type="email" id="email" name="email" class="form-input" value="<?= escape($email ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password *</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-select">
                    <option value="author">Author (Can manage own posts)</option>
                    <option value="editor">Editor (Can manage all posts)</option>
                    <option value="admin">Administrator (Full access)</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate username from full name if empty
document.getElementById('full_name').addEventListener('blur', function() {
    const full = this.value;
    const user = document.getElementById('username');
    if (full && !user.value) {
        // Simple slugify
        const slug = full.toLowerCase()
            .normalize('NFD') // Decompose unicode
            .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
            .replace(/[^a-z0-9]/g, '') // Remove non-alphanumeric
            .substring(0, 20);
        user.value = slug;
    }
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
