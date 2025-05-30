<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $stats = [
        'totalUsers' => 0,
        'totalItems' => 0,
        'claimedItems' => 0,
        'unclaimedItems' => 0
    ];

    // Get total users count
    $usersConn = new mysqli("localhost", "root", "", "users_db");
    if (!$usersConn->connect_error) {
        $userResult = $usersConn->query("SELECT COUNT(*) as total FROM users");
        if ($userResult) {
            $row = $userResult->fetch_assoc();
            $stats['totalUsers'] = (int)$row['total'];
        }
        $usersConn->close();
    }

    // Get posts statistics
    $pdo = new PDO("mysql:host=localhost;dbname=user_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Get total items count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts");
    $stats['totalItems'] = (int)$stmt->fetchColumn();

    // Check if status column exists
    $statusColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0;

    if ($statusColumnExists) {
        $stmt = $pdo->query("SELECT 
            COUNT(CASE WHEN status = 'claimed' THEN 1 END) as claimed,
            COUNT(CASE WHEN status = 'unclaimed' OR status IS NULL THEN 1 END) as unclaimed
        FROM posts");
        $statusCounts = $stmt->fetch();
        $stats['claimedItems'] = (int)($statusCounts['claimed'] ?? 0);
        $stats['unclaimedItems'] = (int)($statusCounts['unclaimed'] ?? 0);
    } else {
        $stats['unclaimedItems'] = $stats['totalItems'];
    }

    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
