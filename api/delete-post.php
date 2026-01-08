<?php declare(strict_types=1);

/**
 * Delete Post API
 * 
 * Handles post deletion with related data cleanup
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get post ID from request
$postId = (int)($_POST['id'] ?? 0);

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

$db = Database::getInstance();

try {
    // Get post details first
    $post = $db->fetchOne(
        "SELECT id, title, author_id, featured_image FROM posts WHERE id = :id",
        ['id' => $postId]
    );
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    // Authorization check: must be author or admin
    $currentUserId = $auth->getUserId();
    $currentUserRole = $auth->getUserRole();
    
    if ($post['author_id'] != $currentUserId && $currentUserRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this post']);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Delete related data (foreign keys will cascade, but we do it explicitly for clarity)
    
    // 1. Delete post-tags relationships
    $db->delete('post_tags', 'post_id = :post_id', ['post_id' => $postId]);
    
    // 2. Delete comments
    $db->delete('comments', 'post_id = :post_id', ['post_id' => $postId]);
    
    // 3. Delete post versions
    $db->delete('post_versions', 'post_id = :post_id', ['post_id' => $postId]);
    
    // 4. Delete page views
    $db->delete('page_views', 'post_id = :post_id', ['post_id' => $postId]);
    
    // 5. Delete from bookmarks
    $db->delete('bookmarks', 'post_id = :post_id', ['post_id' => $postId]);
    
    // 6. Delete from social shares
    $db->delete('social_shares', 'post_id = :post_id', ['post_id' => $postId]);
    
    // 7. Delete from popular content
    $db->delete('popular_content', 'post_id = :post_id', ['post_id' => $postId]);
    
    // 8. Delete the post itself
    $deleted = $db->delete('posts', 'id = :id', ['id' => $postId]);
    
    if (!$deleted) {
        throw new Exception('Failed to delete post');
    }
    
    // 9. Optionally delete featured image file (commented out for safety)
    // if (!empty($post['featured_image'])) {
    //     $imagePath = __DIR__ . '/../assets/uploads/' . $post['featured_image'];
    //     if (file_exists($imagePath)) {
    //         @unlink($imagePath);
    //     }
    // }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Post deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete post: ' . $e->getMessage()
    ]);
}
