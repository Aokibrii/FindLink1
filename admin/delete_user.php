<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'admin@gmail.com') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$user_id = intval($_GET['id']);

try {
    // Connect to users_db
    $conn = new mysqli("localhost", "root", "", "users_db");

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if user exists and is not admin
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    $user = $result->fetch_assoc();

    // Prevent deletion of admin user
    if ($user['email'] === 'admin@gmail.com') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete admin user']);
        exit();
    }

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No user was deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
