<?php declare(strict_types=1);

/**
 * Delete User API
 * 
 * Handles user deletion with safety checks and content reassignment
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
$auth->requireRole('admin');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get user ID from request
$userId = (int)($_POST['id'] ?? 0);
$currentUserId = $auth->getUserId();

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// 1. Prevent self-deletion
if ($userId === $currentUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

$db = Database::getInstance();

try {
    // Get user details
    $user = $db->fetchOne(
        "SELECT id, username, role FROM users WHERE id = :id",
        ['id' => $userId]
    );
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // 2. Prevent deleting the last admin
    if ($user['role'] === 'admin') {
        $adminCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ($adminCount <= 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Cannot delete the last administrator']);
            exit;
        }
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // 3. Reassign posts to current admin
    $db->update(
        'posts',
        ['author_id' => $currentUserId],
        'author_id = :old_id',
        ['old_id' => $userId]
    );
    
    // 4. Delete user
    $deleted = $db->delete('users', 'id = :id', ['id' => $userId]);
    
    if (!$deleted) {
        throw new Exception('Failed to delete user');
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully. Their posts have been reassigned to you.'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete user: ' . $e->getMessage()
    ]);
}
