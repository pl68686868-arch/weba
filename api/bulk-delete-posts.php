<?php declare(strict_types=1);

/**
 * Bulk Delete Posts API
 * 
 * Handles deletion of multiple posts
 * 
 * @package Weba
 * @author Danny Duong
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No IDs provided']);
    exit;
}

$db = Database::getInstance();
$currentUserId = $auth->getUserId();
$currentUserRole = $auth->getUserRole();

try {
    $db->beginTransaction();
    
    $deletedCount = 0;
    
    foreach ($ids as $id) {
        $postId = (int)$id;
        
        // Authorization Check (per post)
        // If not admin, check ownership
        if ($currentUserRole !== 'admin') {
            $post = $db->fetchOne("SELECT author_id FROM posts WHERE id = :id", ['id' => $postId]);
            if (!$post || $post['author_id'] != $currentUserId) {
                continue; // Skip posts we don't own
            }
        }
        
        // Delete Dependencies (Same as delete-post.php)
        $db->delete('post_tags', 'post_id = :id', ['id' => $postId]);
        $db->delete('comments', 'post_id = :id', ['id' => $postId]);
        $db->delete('post_versions', 'post_id = :id', ['id' => $postId]);
        $db->delete('page_views', 'post_id = :id', ['id' => $postId]); // If exists
        $db->delete('bookmarks', 'post_id = :id', ['id' => $postId]);
        $db->delete('social_shares', 'post_id = :id', ['id' => $postId]);
        $db->delete('popular_content', 'post_id = :id', ['id' => $postId]); // If exists
        
        $db->delete('posts', 'id = :id', ['id' => $postId]);
        $deletedCount++;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully deleted $deletedCount posts",
        'deleted_count' => $deletedCount
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
