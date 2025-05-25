<?php
// Start session to check if user is logged in
session_start();

// Initialize response array
$response = array();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['error'] = 'Post ID is required';
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

    // Prepare query to fetch post data
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        $response['error'] = 'Post not found';
        echo json_encode($response);
        exit;
    }

    // Get user name if available
    try {
        // Try to connect to users_db to get user name
        $userPdo = new PDO("mysql:host=$host;dbname=users_db;charset=$charset", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $userStmt = $userPdo->prepare("SELECT name FROM users WHERE email = ?");
        $userStmt->execute([$post['user_email']]);
        $userData = $userStmt->fetch();

        if ($userData) {
            $post['user_name'] = $userData['name'];
        } else {
            $post['user_name'] = 'Unknown';
        }
    } catch (PDOException $e) {
        // If we can't connect to users_db, just continue without user name
        $post['user_name'] = 'Unknown';
        error_log("Error fetching user data: " . $e->getMessage());
    }

    // Include photo URL if exists
    if (!empty($post['photo_path'])) {
        // Make sure the photo path is a URL (not a file system path)
        $post['photo_path'] = str_replace('\\', '/', $post['photo_path']);

        // Ensure photo path exists and is accessible
        if (!file_exists($post['photo_path'])) {
            $post['photo_path'] = 'uploads/placeholder.jpg';  // Use a placeholder if file doesn't exist
        }
    } else {
        $post['photo_path'] = 'uploads/placeholder.jpg';  // Use a placeholder if no photo
    }

    // Process status and type if columns exist
    try {
        $statusColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0;
        $typeColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0;

        if (!$statusColumnExists && !isset($post['status'])) {
            $post['status'] = 'unclaimed';  // Default status
        }

        if (!$typeColumnExists && !isset($post['type'])) {
            $post['type'] = 'lost';  // Default type
        }
    } catch (PDOException $e) {
        error_log("Error checking columns: " . $e->getMessage());
    }

    // Return the post data as JSON
    echo json_encode($post);
} catch (PDOException $e) {
    $response['error'] = 'Database error';
    error_log("Database error in get_post.php: " . $e->getMessage());
    echo json_encode($response);
}
