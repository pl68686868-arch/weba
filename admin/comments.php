<?php declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

// Handle Actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $db->update('comments', ['status' => 'approved'], 'id = :id', ['id' => $id]);
    } elseif ($action === 'delete') {
        $db->delete('comments', 'id = :id', ['id' => $id]);
    }
    redirect('/admin/comments.php');
}

// Fetch Comments
$sql = "SELECT c.*, p.title as post_title 
        FROM comments c 
        JOIN posts p ON c.post_id = p.id 
        ORDER BY c.created_at DESC 
        LIMIT 50";
        
// Note: comments table might be empty, so handle that
$comments = $db->fetchAll($sql);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-header">
    <h1>Quản lý Bình luận</h1>
</div>

<div class="card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Tác giả</th>
                <th>Nội dung</th>
                <th>Bài viết</th>
                <th>Ngày</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Chưa có bình luận nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <tr class="status-<?= $comment['status'] ?>">
                        <td>
                            <strong><?= htmlspecialchars($comment['author_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($comment['author_email']) ?></small>
                        </td>
                        <td>
                            <div class="comment-content">
                                <?= htmlspecialchars(substr($comment['content'], 0, 100)) ?>...
                            </div>
                            <?php if ($comment['status'] === 'pending'): ?>
                                <span class="badge badge-warning">Chờ duyệt</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($comment['post_title']) ?></td>
                        <td><?= date('d/m/Y', strtotime($comment['created_at'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($comment['status'] !== 'approved'): ?>
                                    <a href="?action=approve&id=<?= $comment['id'] ?>" class="btn-icon btn-approve" title="Duyệt">✅</a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?= $comment['id'] ?>" class="btn-icon btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .status-pending { background-color: #fff9e6; }
    .badge-warning { background: #ffc107; color: #000; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
    .comment-content { font-size: 14px; max-width: 300px; color: #555; }
    .btn-icon { text-decoration: none; font-size: 18px; margin-right: 8px; }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
