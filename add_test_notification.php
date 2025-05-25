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

$message = '';
$success = false;

// Check if notifications table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check_table->num_rows == 0) {
    $create_table_sql = "CREATE TABLE notifications (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($create_table_sql) === TRUE) {
        $message .= "Notifications table created successfully.<br>";
    } else {
        $message .= "Error creating table: " . $conn->error . "<br>";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_notification'])) {
        $user_email = $_SESSION['email']; // Current user's email
        $notification_text = $_POST['notification_text'];

        // Insert the notification
        $insert_sql = "INSERT INTO notifications (user_email, message) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ss", $user_email, $notification_text);

        if ($stmt->execute()) {
            $success = true;
            $message = "Notification added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Test Notification</title>
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
        }

        .container {
            max-width: 600px;
        }

        .alert {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Add Test Notification</h1>
        <p>This tool allows you to add test notifications to your account.</p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="notificationText">Notification Text:</label>
                <textarea class="form-control" id="notificationText" name="notification_text" rows="3" required></textarea>
            </div>
            <button type="submit" name="add_notification" class="btn btn-primary mt-3">Add Notification</button>
        </form>

        <div class="mt-4">
            <a href="check_notifications.php" class="btn btn-info">View Notifications Debug</a>
            <a href="notifications.php" class="btn btn-secondary">Go to Notifications Page</a>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>