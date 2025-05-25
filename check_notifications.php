<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
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
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Debugging Notification System</h2>";

// Display current user email
echo "<p>Current logged-in user email: " . $_SESSION['email'] . "</p>";

// 1. Check if notifications table exists
$check_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check_table->num_rows == 0) {
    die("<p>Error: The 'notifications' table does not exist in the database.</p>");
}

// 2. Get table structure
echo "<h3>Notifications Table Structure:</h3>";
$table_structure = $conn->query("DESCRIBE notifications");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $table_structure->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $key => $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// 3. Count all notifications in the table
$count_all = $conn->query("SELECT COUNT(*) as total FROM notifications");
$total_count = $count_all->fetch_assoc()['total'];
echo "<p>Total notifications in the database: $total_count</p>";

// 4. Check notifications for current user
$user_email = $_SESSION['email'];
$user_sql = "SELECT COUNT(*) as total FROM notifications WHERE user_email = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user_count = $result->fetch_assoc()['total'];
echo "<p>Notifications for current user ($user_email): $user_count</p>";

// 5. Display all notifications for current user
echo "<h3>Your Notifications:</h3>";
$sql = "SELECT * FROM notifications WHERE user_email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Message</th><th>Is Read</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['message'] ?? 'N/A') . "</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No notifications found for your account.</p>";
}

// 6. Display some notifications from other users as a sample
echo "<h3>Sample Notifications in Database (All Users):</h3>";
$all_sql = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5";
$all_result = $conn->query($all_sql);

if ($all_result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>User Email</th><th>Message</th><th>Is Read</th><th>Created At</th></tr>";
    while ($row = $all_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['user_email'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['message'] ?? 'N/A') . "</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No notifications found in the database.</p>";
}

$conn->close();
