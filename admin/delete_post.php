<?php
// Start session to check if user is logged in
session_start();

// Initialize response array
$response = array();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['success'] = false;
    $response['message'] = 'Post ID is required';
    echo json_encode($response);
    exit;
}

// Sanitize the ID
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

    if ($post && !empty($post['photo_path'])) {
        // Construct the full path to the photo
        $fullPath = '../' . $post['photo_path'];

        // Delete the photo file if it exists
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    // Now delete the post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);

    // Check if the deletion was successful
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Post deleted successfully';
    } else {
        $response['success'] = false;
        $response['message'] = 'Post not found or already deleted';
    }

    echo json_encode($response);
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Database error in delete_post.php: " . $e->getMessage());
    echo json_encode($response);
}
