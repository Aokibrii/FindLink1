<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$totalUsers = 0;
$totalItems = 0;
$claimedItems = 0;
$unclaimedItems = 0;

try {
    // Connect to users_db for user count
    $usersConn = new mysqli("localhost", "root", "", "users_db");
    if ($usersConn->connect_error) {
        throw new Exception("Connection to users_db failed: " . $usersConn->connect_error);
    }
    $userResult = $usersConn->query("SELECT COUNT(*) as total FROM users");
    if ($userResult) {
        $row = $userResult->fetch_assoc();
        $totalUsers = $row['total'];
    }
    $usersConn->close();

    // Connect to user_db for posts
    $pdo = new PDO("mysql:host=localhost;dbname=user_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts");
    $totalItems = $stmt->fetchColumn();

    $tableInfo = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'");
    $statusColumnExists = $tableInfo->rowCount() > 0;

    if ($statusColumnExists) {
        $stmt = $pdo->query("SELECT 
            COUNT(CASE WHEN status = 'claimed' THEN 1 END) as claimed,
            COUNT(CASE WHEN status = 'unclaimed' OR status IS NULL THEN 1 END) as unclaimed
        FROM posts");
        $statusCounts = $stmt->fetch();
        $claimedItems = $statusCounts['claimed'] ?? 0;
        $unclaimedItems = $statusCounts['unclaimed'] ?? 0;
    } else {
        $unclaimedItems = $totalItems;
    }

    echo json_encode([
        'success' => true,
        'totalUsers' => $totalUsers,
        'totalItems' => $totalItems,
        'claimedItems' => $claimedItems,
        'unclaimedItems' => $unclaimedItems
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading statistics: ' . $e->getMessage()
    ]);
}
