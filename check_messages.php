<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get current user email
$user_email = $_SESSION['email'];

// Count unread messages
$query = "SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_email = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Return result as JSON
header('Content-Type: application/json');
echo json_encode([
    'unread_count' => $row['unread_count']
]);
