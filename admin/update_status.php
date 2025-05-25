<?php
// Start session to check if user is logged in
session_start();
if (!isset($_SESSION['email'])) {
    // Return JSON error if not logged in
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only respond to POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if required data is provided
if (!isset($_POST['post_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Sanitize inputs
$post_id = intval($_POST['post_id']);
$status = strtolower(trim($_POST['status']));

// Validate status value
if ($status !== 'claimed' && $status !== 'unclaimed') {
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
    $tableInfo = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'");
    $statusColumnExists = $tableInfo->rowCount() > 0;

    if (!$statusColumnExists) {
        // Add status column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT 'unclaimed'");
            $statusColumnExists = true;
        } catch (PDOException $e) {
            error_log("Error adding status column: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Could not add status column']);
            exit;
        }
    }

    // Update the post status
    $stmt = $pdo->prepare("UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$status, $post_id]);

    if ($result) {
        $actionText = $status === 'claimed' ? 'claimed' : 'marked as unclaimed';
        echo json_encode(['success' => true, 'message' => "Item successfully $actionText"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating item status']);
    }
} catch (PDOException $e) {
    error_log("Database error in update_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
