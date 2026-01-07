<?php declare(strict_types=1);

/**
 * Categories Management - Admin
 * 
 * Manage the 4 pillars categories
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!$auth->validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            switch ($action) {
                case 'create':
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $description = trim($_POST['description'] ?? '');
                    $displayOrder = (int)($_POST['display_order'] ?? 0);
                    
                    if (empty($name)) {
                        $error = 'Category name is required';
                    } else {
                        if (empty($slug)) {
                            $slug = SEO::createSlug($name);
                        }
                        
                        $db->insert('categories', [
                            'name' => $name,
                            'slug' => $slug,
                            'description' => $description,
                            'display_order' => $displayOrder
                        ]);
                        
                        $success = 'Category created successfully';
                    }
                    break;
                    
                case 'update':
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $description = trim($_POST['description'] ?? '');
                    $displayOrder = (int)($_POST['display_order'] ?? 0);
                    
                    if ($id > 0 && !empty($name)) {
                        $db->update(
                            'categories',
                            [
                                'name' => $name,
                                'slug' => $slug,
                                'description' => $description,
                                'display_order' => $displayOrder
                            ],
                            'id = :id',
                            ['id' => $id]
                        );
                        
                        $success = 'Category updated successfully';
                    }
                    break;
                    
                case 'delete':
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id > 0) {
                        // Check if category has posts
                        $postCount = $db->fetchColumn(
                            "SELECT COUNT(*) FROM posts WHERE category_id = :id",
                            ['id' => $id]
                        );
                        
                        if ($postCount > 0) {
                            $error = "Cannot delete category with {$postCount} posts";
                        } else {
                            $db->delete('categories', 'id = :id', ['id' => $id]);
                            $success = 'Category deleted successfully';
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log('Categories error: ' . $e->getMessage());
            $error = 'An error occurred';
        }
    }
}

// Get all categories
$categories = $db->fetchAll(
    "SELECT c.*, COUNT(p.id) as post_count 
     FROM categories c
     LEFT JOIN posts p ON c.id = p.category_id
     GROUP BY c.id
     ORDER BY c.display_order ASC, c.name ASC"
);

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <h1>Categories Management</h1>
        <button onclick="showCreateForm()" class="btn btn-primary">+ New Category</button>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= escape($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= escape($success) ?></div>
    <?php endif; ?>
    
    <!-- Create Form (hidden by default) -->
    <div id="createForm" class="form-card" style="display: none;">
        <h2>Create New Category</h2>
        <form method="POST" action="">
            <?= $auth->getCSRFInput() ?>
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="name" class="form-label">Name *</label>
                <input type="text" id="name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="slug" class="form-label">Slug (leave empty for auto)</label>
                <input type="text" id="slug" name="slug" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-textarea" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label for="display_order" class="form-label">Display Order</label>
                <input type="number" id="display_order" name="display_order" class="form-input" value="0">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create</button>
                <button type="button" onclick="hideCreateForm()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Categories Table -->
    <div class="table-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Posts</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <strong><?= escape($cat['name']) ?></strong>
                            <?php if ($cat['description']): ?>
                                <br><small><?= escape(substr($cat['description'], 0, 100)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><code><?= escape($cat['slug']) ?></code></td>
                        <td><?= $cat['post_count'] ?></td>
                        <td><?= $cat['display_order'] ?></td>
                        <td>
                            <button onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)" class="btn btn-small btn-secondary">
                                Edit
                            </button>
                            <?php if ($cat['post_count'] == 0): ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                    <?= $auth->getCSRFInput() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Edit Category</h2>
        <form method="POST" action="">
            <?= $auth->getCSRFInput() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id">
            
            <div class="form-group">
                <label for="edit_name" class="form-label">Name *</label>
                <input type="text" id="edit_name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="edit_slug" class="form-label">Slug</label>
                <input type="text" id="edit_slug" name="slug" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="edit_description" class="form-label">Description</label>
                <textarea id="edit_description" name="description" class="form-textarea" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_display_order" class="form-label">Display Order</label>
                <input type="number" id="edit_display_order" name="display_order" class="form-input">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-page__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.form-card, .table-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 32px;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}
</style>

<script>
function showCreateForm() {
    document.getElementById('createForm').style.display = 'block';
}

function hideCreateForm() {
    document.getElementById('createForm').style.display = 'none';
}

function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_name').value = cat.name;
    document.getElementById('edit_slug').value = cat.slug;
    document.getElementById('edit_description').value = cat.description || '';
    document.getElementById('edit_display_order').value = cat.display_order;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php
// Include admin footer
include __DIR__ . '/../includes/admin-footer.php';
?>
