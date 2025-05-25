<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Check if post ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No post ID provided']);
    exit();
}

$post_id = intval($_GET['id']);

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=user_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // First get the photo path to delete the file
    $stmt = $pdo->prepare("SELECT photo_path FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post && !empty($post['photo_path']) && file_exists($post['photo_path'])) {
        unlink($post['photo_path']); // Delete the photo file
    }

    // Delete the post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);

    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
