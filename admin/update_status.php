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

// Check if required parameters are provided
if (!isset($_POST['post_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$post_id = intval($_POST['post_id']);
$status = $_POST['status'];

// Validate status
if (!in_array($status, ['claimed', 'unclaimed'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Database connection parameters
$host = 'localhost';
$db   = 'user_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Check if the post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    // Check if status column exists
    $statusColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0;

    if (!$statusColumnExists) {
        // Add status column if it doesn't exist
        $pdo->exec("ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT 'unclaimed'");
    }

    // Update the post status
    $stmt = $pdo->prepare("UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$status, $post_id]);

    if ($success && $stmt->rowCount() > 0) {
        $message = $status === 'claimed' ? 'Item marked as claimed successfully!' : 'Item marked as unclaimed successfully!';
        echo json_encode([
            'success' => true,
            'message' => $message,
            'new_status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Post not found or no changes made']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
