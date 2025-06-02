<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'admin@gmail.com') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate required fields
if (!isset($_POST['user_id']) || !isset($_POST['name']) || !isset($_POST['email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = intval($_POST['user_id']);
$name = trim($_POST['name']);
$email = trim($_POST['email']);

// Basic validation
if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email cannot be empty']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

try {
    // Connect to users_db
    $conn = new mysqli("localhost", "root", "", "users_db");

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
        exit();
    }

    // Update the user
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes were made']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
