<?php
// Start session to check if user is logged in
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
    exit;
}

$post_id = intval($_GET['id']);

// Database connection parameters
$host = 'localhost';
$db   = 'user_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // First get the photo path to delete the file
    $stmt = $pdo->prepare("SELECT photo_path FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    // Delete the photo file if it exists
    if (!empty($post['photo_path'])) {
        $full_path = '../' . $post['photo_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    // Delete the post from database
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $success = $stmt->execute([$post_id]);

    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Post deleted successfully!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
