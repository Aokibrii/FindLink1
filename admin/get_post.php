<?php
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID is required']);
    exit;
}

$post_id = intval($_GET['id']);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=user_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    // Ensure all fields are set with default values if null
    $post['status'] = $post['status'] ?? 'unclaimed';
    $post['type'] = $post['type'] ?? 'lost';
    $post['description'] = $post['description'] ?? '';
    $post['title'] = $post['title'] ?? '';
    $post['location'] = $post['location'] ?? '';
    $post['date'] = $post['date'] ?? '';
    $post['time'] = $post['time'] ?? '';
    $post['contact_info'] = $post['contact_info'] ?? '';
    $post['photo_path'] = $post['photo_path'] ?? '';

    echo json_encode($post);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
