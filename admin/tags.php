<?php
declare(strict_types=1);

/**
 * Tags Management - Admin
 * 
 * Manage tags with CRUD operations
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SEO.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

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
                    
                    if (empty($name)) {
                        $error = 'Tag name is required';
                    } else {
                        if (empty($slug)) {
                            $slug = SEO::createSlug($name);
                        }
                        
                        $db->insert('tags', [
                            'name' => $name,
                            'slug' => $slug
                        ]);
                        
                        $success = 'Tag created successfully';
                    }
                    break;
                    
                case 'update':
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    
                    if ($id > 0 && !empty($name)) {
                        $db->update(
                            'tags',
                            ['name' => $name, 'slug' => $slug],
                            'id = :id',
                            ['id' => $id]
                        );
                        
                        $success = 'Tag updated successfully';
                    }
                    break;
                    
                case 'delete':
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id > 0) {
                        // Delete tag associations first
                        $db->delete('post_tags', 'tag_id = :id', ['id' => $id]);
                        // Then delete tag
                        $db->delete('tags', 'id = :id', ['id' => $id]);
                        $success = 'Tag deleted successfully';
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log('Tags error: ' . $e->getMessage());
            $error = 'An error occurred';
        }
    }
}

// Get all tags with post counts
$tags = $db->fetchAll(
    "SELECT t.*, COUNT(pt.post_id) as post_count 
     FROM tags t
     LEFT JOIN post_tags pt ON t.id = pt.tag_id
     GROUP BY t.id
     ORDER BY t.name ASC"
);

// Include admin header
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page__header">
        <h1>Tags Management</h1>
        <button onclick="showCreateForm()" class="btn btn-primary">+ New Tag</button>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= escape($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= escape($success) ?></div>
    <?php endif; ?>
    
    <!-- Create Form -->
    <div id="createForm" class="form-card" style="display: none;">
        <h2>Create New Tag</h2>
        <form method="POST" action="">
            <?= $auth->getCSRFInput() ?>
            <input type="hidden" name="action" value="create">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Tag Name *</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="slug" class="form-label">Slug (auto-generated)</label>
                    <input type="text" id="slug" name="slug" class="form-input">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create</button>
                <button type="button" onclick="hideCreateForm()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Tags Grid -->
    <div class="tags-grid">
        <?php foreach ($tags as $tag): ?>
            <div class="tag-card">
                <div class="tag-card__header">
                    <h3><?= escape($tag['name']) ?></h3>
                    <span class="tag-count"><?= $tag['post_count'] ?> posts</span>
                </div>
                <div class="tag-card__slug">
                    <code><?= escape($tag['slug']) ?></code>
                </div>
                <div class="tag-card__actions">
                    <button onclick="editTag(<?= htmlspecialchars(json_encode($tag)) ?>)" class="btn btn-small btn-secondary">
                        Edit
                    </button>
                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this tag?')">
                        <?= $auth->getCSRFInput() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($tags)): ?>
            <div class="empty-state">
                <p>No tags yet. Create your first tag!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Edit Tag</h2>
        <form method="POST" action="">
            <?= $auth->getCSRFInput() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id">
            
            <div class="form-group">
                <label for="edit_name" class="form-label">Tag Name *</label>
                <input type="text" id="edit_name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="edit_slug" class="form-label">Slug</label>
                <input type="text" id="edit_slug" name="slug" class="form-input">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.tags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.tag-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}

.tag-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.tag-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.tag-card h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #2C5F4F;
}

.tag-count {
    font-size: 0.875rem;
    color: #5A5A5A;
    background: #F5F5F5;
    padding: 4px 12px;
    border-radius: 12px;
}

.tag-card__slug {
    margin-bottom: 16px;
}

.tag-card__slug code {
    background: #F5F5F5;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
}

.tag-card__actions {
    display: flex;
    gap: 8px;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 48px;
    color: #5A5A5A;
}
</style>

<script>
function showCreateForm() {
    document.getElementById('createForm').style.display = 'block';
}

function hideCreateForm() {
    document.getElementById('createForm').style.display = 'none';
}

function editTag(tag) {
    document.getElementById('edit_id').value = tag.id;
    document.getElementById('edit_name').value = tag.name;
    document.getElementById('edit_slug').value = tag.slug;
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
