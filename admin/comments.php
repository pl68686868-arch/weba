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

<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1>Độc giả Phản hồi</h1>
            <p>Quản lý và kiểm duyệt các bình luận trên website.</p>
        </div>
    </div>

    <div class="card p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="pl-6">Tác giả</th>
                        <th>Nội dung</th>
                        <th>Bài viết</th>
                        <th>Thời gian</th>
                        <th class="text-right pr-6">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comments)): ?>
                        <tr>
                            <td colspan="5" class="empty-cell">Chưa có bình luận nào cần xử lý.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <tr class="<?= $comment['status'] === 'pending' ? 'row-pending' : '' ?>">
                                <td class="pl-6">
                                    <div class="author-info">
                                        <div class="author-name"><?= htmlspecialchars($comment['author_name']) ?></div>
                                        <div class="author-email"><?= htmlspecialchars($comment['author_email']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="comment-content">
                                        <?= htmlspecialchars($comment['content']) ?>
                                    </div>
                                    <span class="status-badge status-<?= $comment['status'] === 'pending' ? 'draft' : 'published' ?>">
                                        <?= $comment['status'] === 'pending' ? '🕒 Chờ duyệt' : '✅ Đã duyệt' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="post-title-link" title="<?= htmlspecialchars($comment['post_title']) ?>">
                                        <?= htmlspecialchars($comment['post_title']) ?>
                                    </div>
                                </td>
                                <td class="comment-date">
                                    <?= date('d/m/Y', strtotime($comment['created_at'])) ?>
                                </td>
                                <td class="text-right pr-6">
                                    <div class="table-actions">
                                        <?php if ($comment['status'] !== 'approved'): ?>
                                            <a href="?action=approve&id=<?= $comment['id'] ?>" class="btn-icon btn-icon-primary" title="Duyệt">
                                                ✓
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $comment['id'] ?>" class="btn-icon btn-icon-danger" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                            ✕
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .p-0 { padding: 0; }
    .pl-6 { padding-left: 1.5rem; }
    .pr-6 { padding-right: 1.5rem; }
    .text-right { text-align: right; }
    
    .empty-cell { text-align: center; padding: 64px !important; color: var(--text-muted); }
    .row-pending { background-color: rgba(236, 182, 19, 0.04); }
    
    .author-info { display: flex; flex-direction: column; gap: 2px; }
    .author-name { font-weight: 600; color: var(--text-main); }
    .author-email { font-size: 0.75rem; color: var(--text-muted); }
    
    .comment-content { font-size: 0.875rem; line-height: 1.6; color: var(--text-main); max-width: 450px; margin-bottom: 8px; }
    
    .post-title-link { font-size: 0.8125rem; font-weight: 500; color: var(--color-primary); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; transition: color 0.2s; }
    .post-title-link:hover { color: var(--color-primary-light); }
    
    .comment-date { font-size: 0.8125rem; color: var(--text-muted); }
    
    .table-actions { display: flex; gap: 8px; justify-content: flex-end; }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
