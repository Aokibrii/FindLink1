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

// Fetch notifications for the current user
$user_email = $_SESSION['email'];
$sql = "SELECT * FROM notifications WHERE user_email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Lost and Found</title>
    <link rel="icon" href="images/Icon.jpg">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/user_page.css">
    <link rel="stylesheet" href="css/notifications.css">
</head>

<body>
    <div class="header">
        <div class="side-nav" id="sideNav">
            <a href="user_page.php" class="logo">
                <img src="images/Icon.jpg" class="logo-img">
            </a>
            <ul class="nav-links">
                <li>
                    <a href="user_page.php" class="side-nav-item">
                        <i class="fa-solid fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li>
                    <a href="notifications.php" class="side-nav-item active">
                        <i class="fa-solid fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li>
                    <a href="messages.php" class="side-nav-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </li>
                <li>
                    <a href="Profile.php" class="side-nav-item">
                        <i class="fa-solid fa-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
            <!-- Toggle button for sidebar -->
            <div class="toggle-btn" id="toggleNav">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>

        <!-- Main content with proper ID -->
        <div id="mainContent">
            <div class="notifications-container">
                <h1>Notifications</h1>
                <div class="notifications-list">
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $isRead = $row['is_read'] ? '' : 'unread';
                    ?>
                            <div class="notification-item <?php echo $isRead; ?>">
                                <div class="notification-icon">
                                    <i class="fa-solid fa-bell"></i>
                                </div>
                                <div class="notification-content">
                                    <p class="notification-text"><?php echo htmlspecialchars($row['message']); ?></p>
                                    <span class="notification-time"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></span>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo '<div class="no-notifications">No notifications yet</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar.js"></script>
</body>

</html>
<?php
$conn->close();
?>