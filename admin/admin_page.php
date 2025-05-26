<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php?show=login");
    exit();
}

// Define user email and admin status
$user_email = $_SESSION['email'];
$is_admin = ($user_email === 'admin@gmail.com');

// === Dashboard Statistics Calculation (moved to top) ===
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
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error loading statistics. Please try again later.</div>";
    error_log("Dashboard statistics error: " . $e->getMessage());
}
// === End Dashboard Statistics Calculation ===
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost and Found</title>
    <link rel="icon" href="../images/Icon.jpg">
    <link rel="stylesheet" href="../vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_page.css">
    <!-- Add no-cache headers to prevent browser caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        /* Basic Reset and Common Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            display: flex;
            overflow-x: hidden;
        }

        /* New Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 70px;
            height: 100vh;
            background-color: #ffffff;
            z-index: 1002;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: width 0.3s ease;
            overflow-y: auto;
            border-right: 1px solid #e3e6f0;
        }

        .sidebar.expanded {
            width: 240px;
        }

        .sidebar-header {
            padding: 15px 0;
            text-align: center;
            border-bottom: 1px solid #e3e6f0;
        }

        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin: 5px 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #5a5c69;
            text-decoration: none;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .sidebar a:hover {
            background-color: #eaecf4;
            color: #4e73df;
        }

        .sidebar a i {
            font-size: 20px;
            width: 40px;
            text-align: center;
            color: #5a5c69;
        }

        .sidebar a span {
            margin-left: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar.expanded a span {
            opacity: 1;
        }

        /* Active state for sidebar links */
        .sidebar a.active {
            background-color: #4e73df;
            color: white;
        }

        .sidebar a.active i {
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 70px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 240px;
        }

        /* Admin Panel Section Styles */
        .welcome-section {
            padding: 20px;
            text-align: center;
        }

        .navbar {
            background-color: #343a40;
            padding: 1rem;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }

        .nav-link {
            color: rgba(255, 255, 255, .8) !important;
        }

        .nav-link:hover {
            color: white !important;
        }

        .admin-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .admin-panel-header h2 {
            margin: 0;
        }

        .admin-panel-buttons {
            display: flex;
            gap: 10px;
        }

        .date-time-column {
            display: inline-flex;
            align-items: center;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .date-time-column i {
            color: #3a86ff;
        }

        .date-time-display {
            display: inline-flex;
            align-items: center;
            background-color: rgba(58, 134, 255, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 500;
        }

        .location-display {
            display: inline-flex;
            align-items: center;
            background-color: rgba(244, 67, 54, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 500;
        }

        .contact-display {
            display: inline-flex;
            align-items: center;
            background-color: rgba(76, 175, 80, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 500;
        }

        .user-info {
            display: inline-flex;
            align-items: center;
            font-weight: 500;
        }

        .user-email {
            font-weight: normal;
            color: #6c757d;
            font-size: 0.9em;
        }

        .item-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.05em;
        }

        .info-grid {
            display: grid;
            grid-gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            margin-bottom: 0.5rem;
        }

        .post-meta {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding-bottom: 1rem;
        }

        /* Improve table row appearance */
        .table tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
        }

        /* Toggle button for sidebar */
        .sidebar-toggle {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            background-color: #4e73df;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            cursor: pointer;
            z-index: 1003;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Add this to your existing styles section */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }

        .table-container table {
            position: relative;
        }

        .table-container thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        /* Style the scrollbar */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
    <!-- Add this script to prevent back button navigation -->
    <script type="text/javascript">
        // Disable back button
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, "", window.location.href);
        };

        // On page load
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent back button navigation
            window.history.forward();
        });
    </script>
</head>

<body>
    <!-- New Sidebar Menu -->
    <div class="sidebar" id="sidebar-menu">
        <div class="sidebar-header">
            <img src="../images/Icon.jpg" alt="Admin">
        </div>
        <ul>
            <li><a href="#" id="dashboard-link" class="active"><i class="fas fa-chart-bar"></i><span>Dashboard</span></a></li>
            <li><a href="#" id="view-items-link"><i class="fas fa-box-open"></i><span>View Items</span></a></li>
            <li><a href="#" id="manage-posts-link"><i class="fas fa-thumbtack"></i><span>Manage Posts</span></a></li>
            <li><a href="#" id="manage-users-link"><i class="fas fa-users-cog"></i><span>Manage Users</span></a></li>
            <li><a href="./logout.php" id="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Toggle button for sidebar -->
    <div class="sidebar-toggle" id="sidebar-toggle">
        <i class="fas fa-chevron-right" id="toggle-icon"></i>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <section class="welcome-section">
            <h1>ADMIN PANEL</h1>
        </section>
        <!-- Dashboard Statistics Section -->
        <section id="dashboardStatisticsSection" class="mb-4">
            <div class="row g-4 justify-content-center">
                <div class="col-md-3">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h3 class="count mb-1"><?php echo $totalUsers; ?></h3>
                            <div class="label text-muted">Total Users</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body">
                            <i class="fas fa-box-open fa-2x text-info mb-2"></i>
                            <h3 class="count mb-1"><?php echo $totalItems; ?></h3>
                            <div class="label text-muted">Total Items</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h3 class="count mb-1"><?php echo $claimedItems; ?></h3>
                            <div class="label text-muted">Claimed Items</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body">
                            <i class="fas fa-exclamation-circle fa-2x text-warning mb-2"></i>
                            <h3 class="count mb-1"><?php echo $unclaimedItems; ?></h3>
                            <div class="label text-muted">Unclaimed Items</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- View Items Section (hidden by default) -->
        <section id="viewItemsSection" class="mb-4 d-none">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-box-open me-2"></i>View All Items</h5>
                    <button class="btn btn-sm btn-light" onclick="closeSection('viewItemsSection')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        if ($is_admin) {
                            // Admin sees all posts
                            $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
                        } else {
                            // User sees only their posts
                            $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_email = ? ORDER BY created_at DESC");
                            $stmt->execute([$user_email]);
                        }
                        $posts = $stmt->fetchAll();

                        if (count($posts) > 0) {
                            echo "<div class='table-container'>";
                            echo "<table class='table table-bordered table-hover'>
                                <thead class='table-dark'>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Title</th>
                                        <th>Photo</th>
                                        <th>Location</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>";

                            foreach ($posts as $post) {
                                $statusClass = isset($post['status']) && $post['status'] === 'claimed' ? 'success' : 'warning';
                                $statusText = isset($post['status']) && $post['status'] === 'claimed' ? 'Claimed' : 'Unclaimed';
                                $typeText = ucfirst($post['type'] ?? 'unknown');

                                echo "<tr data-post-id='{$post['id']}'>
                                    <td>{$post['id']}</td>
                                    <td><span class='user-info'><i class='fas fa-user text-info me-2'></i>{$post['user_email']}</span></td>
                                    <td><span class='item-title'>{$post['title']}</span></td>
                                    <td><img src='../{$post['photo_path']}' alt='Post Photo' class='img-thumbnail' style='max-width: 100px;'></td>
                                    <td><i class='fas fa-map-marker-alt text-danger me-2'></i>{$post['location']}</td>
                                    <td><span class='date-time-column'><i class='far fa-calendar-alt me-1'></i> {$post['date']} <i class='far fa-clock ms-2 me-1'></i> {$post['time']}</span></td>
                                    <td><span class='badge bg-secondary'>{$typeText}</span></td>
                                    <td><span class='badge bg-{$statusClass}'>{$statusText}</span></td>
                                    <td><i class='fas fa-phone-alt text-success me-2'></i>{$post['contact_info']}</td>
                                    <td>
                                        <div class='d-flex flex-column gap-2'>
                                            <button class='btn btn-sm btn-danger' onclick='deletePost({$post['id']})'>Delete</button>
                                            <button class='btn btn-sm " . ($post['status'] == 'claimed' ? 'btn-outline-warning' : 'btn-outline-success') . "' 
                                                    onclick='updateItemStatus({$post['id']}, \"" . ($post['status'] == 'claimed' ? 'unclaimed' : 'claimed') . "\")'>
                                                <i class='fas fa-" . ($post['status'] == 'claimed' ? 'times-circle' : 'check-circle') . " me-1'></i>
                                                " . ($post['status'] == 'claimed' ? 'Mark Unclaimed' : 'Mark Claimed') . "
                                            </button>
                                        </div>
                                    </td>
                                </tr>";
                            }

                            echo "</tbody></table>";
                            echo "</div>";
                        } else {
                            echo "<div class='alert alert-info'>No items found.</div>";
                        }
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-danger'>Error loading items: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Manage Posts Section (hidden by default) -->
        <section id="managePostsSection" class="mb-4 d-none">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-thumbtack me-2"></i>Manage Posts</h5>
                    <button class="btn btn-sm btn-light" onclick="closeSection('managePostsSection')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        // Connect to database
                        $host = 'localhost';
                        $db   = 'user_db';
                        $user = 'root';
                        $pass = '';
                        $charset = 'utf8mb4';

                        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]);

                        // Handle post deletion
                        if (isset($_GET['delete_post'])) {
                            $delete_id = intval($_GET['delete_post']);
                            // First get the photo path to delete the file
                            $stmt = $pdo->prepare("SELECT photo_path FROM posts WHERE id = ?");
                            $stmt->execute([$delete_id]);
                            $post = $stmt->fetch();

                            if ($post && !empty($post['photo_path']) && file_exists($post['photo_path'])) {
                                unlink($post['photo_path']); // Delete the photo file
                            }

                            // Now delete the post
                            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                            $stmt->execute([$delete_id]);
                            echo "<div class='alert alert-success'>Post deleted successfully!</div>";
                        }

                        // Handle post update
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
                            $edit_id = intval($_POST['post_id']);
                            $edit_title = $_POST['edit_title'];
                            $edit_description = $_POST['edit_description'];
                            $edit_location = $_POST['edit_location'];
                            $edit_date = $_POST['edit_date'];
                            $edit_time = $_POST['edit_time'];
                            $edit_contact_info = $_POST['edit_contact_info'];
                            $edit_status = $_POST['edit_status'] ?? 'unclaimed';
                            $edit_type = $_POST['edit_type'] ?? 'lost';

                            // Handle photo update if a new one is uploaded
                            $photo_path_update = "";
                            if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
                                $fileTmpPath = $_FILES['edit_photo']['tmp_name'];
                                $fileName = basename($_FILES['edit_photo']['name']);
                                $fileSize = $_FILES['edit_photo']['size'];
                                $fileType = $_FILES['edit_photo']['type'];
                                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

                                if (!in_array($fileType, $allowedTypes)) {
                                    echo "<div class='alert alert-danger'>Only JPG, PNG, and GIF files are allowed.</div>";
                                } elseif ($fileSize > 5 * 1024 * 1024) {
                                    echo "<div class='alert alert-danger'>File size should not exceed 5MB.</div>";
                                } else {
                                    // Get the old photo path to delete it
                                    $stmt = $pdo->prepare("SELECT photo_path FROM posts WHERE id = ?");
                                    $stmt->execute([$edit_id]);
                                    $old_post = $stmt->fetch();

                                    if ($old_post && !empty($old_post['photo_path']) && file_exists($old_post['photo_path'])) {
                                        unlink($old_post['photo_path']); // Delete the old photo
                                    }

                                    $uploadDir = '../uploads/';
                                    if (!is_dir($uploadDir)) {
                                        mkdir($uploadDir, 0777, true);
                                    }
                                    $destPath = $uploadDir . uniqid() . '_' . $fileName;
                                    // Use relative path for database storage without the '../'
                                    $dbPath = 'uploads/' . basename($destPath);
                                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                                        $photo_path_update = ", photo_path = '$dbPath'";
                                    }
                                }
                            }

                            // Check if status and type columns exist
                            $statusColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0;
                            $typeColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0;

                            // Build the UPDATE query based on existing columns
                            $updateSQL = "UPDATE posts SET 
                                        title = ?, 
                                        description = ?, 
                                        location = ?, 
                                        date = ?, 
                                        time = ?, 
                                        contact_info = ?, 
                                        updated_at = NOW()";

                            $params = [
                                $edit_title,
                                $edit_description,
                                $edit_location,
                                $edit_date,
                                $edit_time,
                                $edit_contact_info
                            ];

                            if ($statusColumnExists) {
                                $updateSQL .= ", status = ?";
                                $params[] = $edit_status;
                            }

                            if ($typeColumnExists) {
                                $updateSQL .= ", type = ?";
                                $params[] = $edit_type;
                            }

                            $updateSQL .= $photo_path_update . " WHERE id = ?";
                            $params[] = $edit_id;

                            // Update the post
                            $stmt = $pdo->prepare($updateSQL);
                            $stmt->execute($params);
                            echo "<div class='alert alert-success'>Post updated successfully!</div>";
                        }

                        // First fetch all posts
                        $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
                        $posts = $stmt->fetchAll();

                        // Then loop through posts and fetch user details separately
                        foreach ($posts as $key => $post) {
                            $userStmt = $pdo->prepare("SELECT name FROM users_db.users WHERE email = ?");
                            $userStmt->execute([$post['user_email']]);
                            $user = $userStmt->fetch();
                            $posts[$key]['user_name'] = $user ? $user['name'] : 'Unknown';
                        }

                        if (count($posts) > 0) {
                            echo "<div class='table-container'>";
                            echo "<table class='table table-bordered table-hover'>
                              <thead class='table-dark'>
                                <tr>
                                  <th>ID</th>
                                  <th>User</th>
                                  <th>Title</th>
                                  <th>Photo</th>
                                  <th>Location</th>
                                  <th>Date</th>";

                            // Check if type column exists
                            if ($pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0) {
                                echo "<th>Type</th>";
                            }

                            // Check if status column exists
                            if ($pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0) {
                                echo "<th>Status</th>";
                            }

                            echo "<th>Contact</th>
                                  <th>Actions</th>
                                </tr>
                              </thead>
                              <tbody>";

                            foreach ($posts as $post) {
                                $statusClass = isset($post['status']) && $post['status'] === 'claimed' ? 'success' : 'warning';
                                $statusText = isset($post['status']) && $post['status'] === 'claimed' ? 'Claimed' : 'Unclaimed';

                                echo "<tr>
                                <td>{$post['id']}</td>
                                <td><span class='user-info'><i class='fas fa-user text-info me-2'></i>{$post['user_name']} <span class='user-email'>({$post['user_email']})</span></span></td>
                                <td><span class='item-title'>{$post['title']}</span></td>
                                <td><img src='../{$post['photo_path']}' alt='Post Photo' class='img-thumbnail' style='max-width: 100px;'></td>
                                <td><i class='fas fa-map-marker-alt text-danger me-2'></i>{$post['location']}</td>
                                <td><span class='date-time-column'><i class='far fa-calendar-alt me-1'></i> {$post['date']} <i class='far fa-clock ms-2 me-1'></i> {$post['time']}</span></td>";

                                // Show type if column exists
                                if (isset($post['type'])) {
                                    $typeText = ucfirst($post['type']);
                                    echo "<td><span class='badge bg-secondary'>{$typeText}</span></td>";
                                }

                                // Show status if column exists
                                if (isset($post['status'])) {
                                    echo "<td><span class='badge bg-{$statusClass}'>{$statusText}</span></td>";
                                }

                                echo "<td><i class='fas fa-phone-alt text-success me-2'></i>{$post['contact_info']}</td>
                                <td>
                                    <button class='btn btn-sm btn-warning mb-1' onclick='editPost({$post['id']})'>Edit</button>
                                    <button class='btn btn-sm btn-danger' onclick='deletePost({$post['id']})'>Delete</button>
                                </td>
                              </tr>";
                            }

                            echo "</tbody></table>";
                            echo "</div>"; // Close table-container
                        } else {
                            echo "<div class='alert alert-info'>No posts found.</div>";
                        }
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>

                    <!-- Edit Post Form (hidden, shown via JS) -->
                    <div id="editPostForm" class="card mt-4 d-none">
                        <div class="card-header bg-warning text-dark">
                            <h5>Edit Post</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="post_id" id="edit_post_id">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_title" class="form-label">Title</label>
                                        <input type="text" name="edit_title" id="edit_title" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_location" class="form-label">Location</label>
                                        <input type="text" name="edit_location" id="edit_location" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_date" class="form-label">Date</label>
                                        <input type="date" name="edit_date" id="edit_date" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_time" class="form-label">Time</label>
                                        <input type="time" name="edit_time" id="edit_time" class="form-control">
                                    </div>
                                </div>

                                <?php
                                // Check if status and type columns exist
                                try {
                                    $statusColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0;
                                    $typeColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0;

                                    if ($statusColumnExists || $typeColumnExists) {
                                        echo '<div class="row">';

                                        if ($statusColumnExists) {
                                            echo '<div class="col-md-6 mb-3">
                                                    <label for="edit_status" class="form-label">Status</label>
                                                    <select name="edit_status" id="edit_status" class="form-select">
                                                        <option value="unclaimed">Unclaimed</option>
                                                        <option value="claimed">Claimed</option>
                                                    </select>
                                                </div>';
                                        }

                                        if ($typeColumnExists) {
                                            echo '<div class="col-md-6 mb-3">
                                                    <label for="edit_type" class="form-label">Type</label>
                                                    <select name="edit_type" id="edit_type" class="form-select">
                                                        <option value="lost">Lost</option>
                                                        <option value="found">Found</option>
                                                    </select>
                                                </div>';
                                        }

                                        echo '</div>';
                                    }
                                } catch (Exception $e) {
                                    error_log("Error checking columns: " . $e->getMessage());
                                }
                                ?>

                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea name="edit_description" id="edit_description" class="form-control" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_contact_info" class="form-label">Contact Information</label>
                                    <input type="text" name="edit_contact_info" id="edit_contact_info" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_photo" class="form-label">Photo (leave empty to keep current)</label>
                                    <input type="file" name="edit_photo" id="edit_photo" class="form-control">
                                    <div id="current_photo_preview" class="mt-2"></div>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" name="edit_post" class="btn btn-success">Update Post</button>
                                    <button type="button" class="btn btn-secondary" onclick="hideEditForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Manage Users Section (hidden by default) -->
        <section id="manageUsersSection" class="mb-4 d-none">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Manage Users</h5>
                    <button class="btn btn-sm btn-light" onclick="closeSection('manageUsersSection')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="editUserForm" class="card mt-4 d-none">
                        <div class="card-header bg-warning text-dark">
                            <h5>Edit User</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="user_id" id="edit_user_id">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Name</label>
                                    <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" name="edit_user" class="btn btn-success">Update User</button>
                                    <button type="button" class="btn btn-secondary" onclick="hideEditUserForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    // Connect to users_db
                    $conn = new mysqli("localhost", "root", "", "users_db");
                    if ($conn->connect_error) {
                        echo "<div class='alert alert-danger'>Connection failed: " . $conn->connect_error . "</div>";
                    } else {
                        // Handle user deletion
                        if (isset($_GET['delete_user'])) {
                            $delete_id = intval($_GET['delete_user']);
                            $conn->query("DELETE FROM users WHERE id=$delete_id");
                            echo "<div class='alert alert-success'>User deleted successfully!</div>";
                        }
                        // Handle user update
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
                            $edit_id = intval($_POST['user_id']);
                            $edit_name = $conn->real_escape_string($_POST['edit_name']);
                            $edit_email = $conn->real_escape_string($_POST['edit_email']);
                            $conn->query("UPDATE users SET name='$edit_name', email='$edit_email' WHERE id=$edit_id");
                            echo "<div class='alert alert-success'>User updated successfully!</div>";
                        }
                        // Fetch users
                        $result = $conn->query("SELECT id, name, email FROM users");
                        echo "<div class='table-container'>";
                        echo "<table class='table table-bordered'><thead class='table-dark'><tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr></thead><tbody>";
                        while ($row = $result->fetch_assoc()) {
                            $is_admin = ($row['email'] === 'admin@gmail.com');

                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>
                                    <button class='btn btn-sm btn-warning' onclick='editUser({$row['id']}, \"{$row['name']}\", \"{$row['email']}\")'>Edit</button>";

                            // Only show delete button if not admin
                            if (!$is_admin) {
                                echo " <a href='?delete_user={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete this user?\")'>Delete</a>";
                            }

                            echo "</td>
                            </tr>";
                        }
                        echo "</tbody></table>";
                        echo "</div>"; // Close table-container
                        $conn->close();
                    }
                    ?>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the elements
            const sidebar = document.getElementById('sidebar-menu');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const toggleIcon = document.getElementById('toggle-icon');
            const menuLinks = document.querySelectorAll('.sidebar a');

            // Function to close a section and return to dashboard
            function closeSection(sectionId) {
                // Hide the section
                document.getElementById(sectionId).classList.add('d-none');

                // Show dashboard statistics
                document.getElementById('dashboardStatisticsSection').classList.remove('d-none');

                // Set dashboard as active menu item
                setActiveMenuItem(document.getElementById('dashboard-link'));

                // Scroll to dashboard
                document.getElementById('dashboardStatisticsSection').scrollIntoView({
                    behavior: 'smooth'
                });

                // Adjust table containers
                adjustTableContainers();
            }

            // Make closeSection available globally
            window.closeSection = closeSection;

            // Set active menu item
            function setActiveMenuItem(element) {
                // Remove active class from all menu items
                menuLinks.forEach(link => link.classList.remove('active'));
                // Add active class to the clicked item
                if (element) {
                    element.classList.add('active');
                }
            }

            // Toggle sidebar function
            function toggleSidebar() {
                sidebar.classList.toggle('expanded');
                mainContent.classList.toggle('expanded');

                if (sidebar.classList.contains('expanded')) {
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-chevron-left');
                } else {
                    toggleIcon.classList.remove('fa-chevron-left');
                    toggleIcon.classList.add('fa-chevron-right');
                }

                // Adjust table containers after sidebar toggle
                adjustTableContainers();
            }

            // Function to adjust table containers based on sidebar state
            function adjustTableContainers() {
                const tableContainers = document.querySelectorAll('.table-container');

                // Timeout ensures the adjustment happens after the sidebar transition
                setTimeout(() => {
                    tableContainers.forEach(container => {
                        // Trigger reflow to ensure proper alignment
                        container.style.maxWidth = '';

                        // Set max-width based on parent container's width
                        const parentWidth = container.parentElement.clientWidth;
                        container.style.maxWidth = parentWidth + 'px';
                    });
                }, 300); // Match this to your sidebar transition duration
            }

            // Call adjust on window resize too
            window.addEventListener('resize', adjustTableContainers);

            // Initial adjustment
            adjustTableContainers();

            // Function to show a section and adjust its tables
            function showSection(sectionId) {
                const section = document.getElementById(sectionId);
                section.classList.remove('d-none');
                section.scrollIntoView({
                    behavior: 'smooth'
                });

                // Adjust table containers in the section
                adjustTableContainers();
            }

            // Event listeners
            sidebarToggle.addEventListener('click', toggleSidebar);

            // Set up click handlers for all menu links
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    setActiveMenuItem(this);
                });
            });

            // View Items link
            document.getElementById('view-items-link').addEventListener('click', function(e) {
                e.preventDefault();
                setActiveMenuItem(this);

                // Hide dashboard statistics section
                document.getElementById('dashboardStatisticsSection').classList.add('d-none');

                // Hide manage posts section if visible
                document.getElementById('managePostsSection').classList.add('d-none');

                // Hide manage users section if visible
                document.getElementById('manageUsersSection').classList.add('d-none');

                // Show view items section
                showSection('viewItemsSection');
            });

            // Manage Posts link
            document.getElementById('manage-posts-link').addEventListener('click', function(e) {
                e.preventDefault();
                setActiveMenuItem(this);

                // Hide dashboard statistics section
                document.getElementById('dashboardStatisticsSection').classList.add('d-none');

                // Hide view items section if visible
                document.getElementById('viewItemsSection').classList.add('d-none');

                // Hide manage users section if visible
                document.getElementById('manageUsersSection').classList.add('d-none');

                // Show manage posts section
                showSection('managePostsSection');
            });

            // Manage Users link
            document.getElementById('manage-users-link').addEventListener('click', function(e) {
                e.preventDefault();
                setActiveMenuItem(this);

                // Hide dashboard statistics section
                document.getElementById('dashboardStatisticsSection').classList.add('d-none');

                // Hide view items section if visible
                document.getElementById('viewItemsSection').classList.add('d-none');

                // Hide manage posts section if visible
                document.getElementById('managePostsSection').classList.add('d-none');

                // Show manage users section
                showSection('manageUsersSection');
            });

            // Dashboard link
            document.getElementById('dashboard-link').addEventListener('click', function(e) {
                e.preventDefault();
                setActiveMenuItem(this);

                // Show dashboard statistics
                document.getElementById('dashboardStatisticsSection').classList.remove('d-none');

                // Hide view items section
                document.getElementById('viewItemsSection').classList.add('d-none');

                // Hide manage posts section
                document.getElementById('managePostsSection').classList.add('d-none');

                // Hide manage users section
                document.getElementById('manageUsersSection').classList.add('d-none');

                document.getElementById('dashboardStatisticsSection').scrollIntoView({
                    behavior: 'smooth'
                });
            });

            // Logout link
            document.getElementById('logout-link').addEventListener('click', function(e) {
                e.preventDefault();
                setActiveMenuItem(this);
                var modal = new bootstrap.Modal(document.getElementById('logoutModal'));
                modal.show();
            });
        });
    </script>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header border-0 justify-content-center">
                    <div class="w-100">
                        <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                        <h5 class="modal-title w-100" id="logoutModalLabel">Confirm Logout</h5>
                    </div>
                    <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="fs-5 mb-4">Are you sure you want to logout?</p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-3">
                    <a href="./logout.php" class="btn btn-danger px-4">Logout</a>
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="managePostsModal" tabindex="-1" aria-labelledby="managePostsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="managePostsModalLabel">Manage User Posts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    // Connect to database
                    $host = 'localhost';
                    $db   = 'user_db';
                    $user = 'root';
                    $pass = '';
                    $charset = 'utf8mb4';

                    try {
                        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]);

                        // Handle post deletion
                        if (isset($_GET['delete_post'])) {
                            $delete_id = intval($_GET['delete_post']);
                            // First get the photo path to delete the file
                            $stmt = $pdo->prepare("SELECT photo_path FROM posts WHERE id = ?");
                            $stmt->execute([$delete_id]);
                            $post = $stmt->fetch();

                            if ($post && !empty($post['photo_path']) && file_exists($post['photo_path'])) {
                                unlink($post['photo_path']); // Delete the photo file
                            }

                            // Now delete the post
                            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                            $stmt->execute([$delete_id]);
                            echo "<div class='alert alert-success'>Post deleted successfully!</div>";
                        }

                        // Handle post update
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
                            $edit_id = intval($_POST['post_id']);
                            $edit_title = $_POST['edit_title'];
                            $edit_description = $_POST['edit_description'];
                            $edit_location = $_POST['edit_location'];
                            $edit_date = $_POST['edit_date'];
                            $edit_time = $_POST['edit_time'];
                            $edit_contact_info = $_POST['edit_contact_info'];
                            $edit_status = $_POST['edit_status'] ?? 'unclaimed';
                            $edit_type = $_POST['edit_type'] ?? 'lost';

                            // Handle photo update if a new one is uploaded
                            $photo_path_update = "";
                            if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
                                $fileTmpPath = $_FILES['edit_photo']['tmp_name'];
                                $fileName = basename($_FILES['edit_photo']['name']);
                                $fileSize = $_FILES['edit_photo']['size'];
                                $fileType = $_FILES['edit_photo']['type'];
                                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

                                if (!in_array($fileType, $allowedTypes)) {
                                    echo "<div class='alert alert-danger'>Only JPG, PNG, and GIF files are allowed.</div>";
                                } elseif ($fileSize > 5 * 1024 * 1024) {
                                    echo "<div class='alert alert-danger'>File size should not exceed 5MB.</div>";
                                } else {
                                    // Get the old photo path to delete it
                                    $stmt = $pdo->prepare("SELECT photo_path FROM posts WHERE id = ?");
                                    $stmt->execute([$edit_id]);
                                    $old_post = $stmt->fetch();

                                    if ($old_post && !empty($old_post['photo_path']) && file_exists($old_post['photo_path'])) {
                                        unlink($old_post['photo_path']); // Delete the old photo
                                    }

                                    $uploadDir = '../uploads/';
                                    if (!is_dir($uploadDir)) {
                                        mkdir($uploadDir, 0777, true);
                                    }
                                    $destPath = $uploadDir . uniqid() . '_' . $fileName;
                                    // Use relative path for database storage without the '../'
                                    $dbPath = 'uploads/' . basename($destPath);
                                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                                        $photo_path_update = ", photo_path = '$dbPath'";
                                    }
                                }
                            }

                            // Check if status and type columns exist
                            $statusColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0;
                            $typeColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0;

                            // Build the UPDATE query based on existing columns
                            $updateSQL = "UPDATE posts SET 
                                        title = ?, 
                                        description = ?, 
                                        location = ?, 
                                        date = ?, 
                                        time = ?, 
                                        contact_info = ?, 
                                        updated_at = NOW()";

                            $params = [
                                $edit_title,
                                $edit_description,
                                $edit_location,
                                $edit_date,
                                $edit_time,
                                $edit_contact_info
                            ];

                            if ($statusColumnExists) {
                                $updateSQL .= ", status = ?";
                                $params[] = $edit_status;
                            }

                            if ($typeColumnExists) {
                                $updateSQL .= ", type = ?";
                                $params[] = $edit_type;
                            }

                            $updateSQL .= $photo_path_update . " WHERE id = ?";
                            $params[] = $edit_id;

                            // Update the post
                            $stmt = $pdo->prepare($updateSQL);
                            $stmt->execute($params);
                            echo "<div class='alert alert-success'>Post updated successfully!</div>";
                        }

                        // First fetch all posts
                        $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
                        $posts = $stmt->fetchAll();

                        // Then loop through posts and fetch user details separately
                        foreach ($posts as $key => $post) {
                            $userStmt = $pdo->prepare("SELECT name FROM users_db.users WHERE email = ?");
                            $userStmt->execute([$post['user_email']]);
                            $user = $userStmt->fetch();
                            $posts[$key]['user_name'] = $user ? $user['name'] : 'Unknown';
                        }

                        if (count($posts) > 0) {
                            echo "<div class='table-container'>";
                            echo "<table class='table table-bordered table-hover'>
                              <thead class='table-dark'>
                                <tr>
                                  <th>ID</th>
                                  <th>User</th>
                                  <th>Title</th>
                                  <th>Photo</th>
                                  <th>Location</th>
                                  <th>Date</th>";

                            // Check if type column exists
                            if ($pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0) {
                                echo "<th>Type</th>";
                            }

                            // Check if status column exists
                            if ($pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0) {
                                echo "<th>Status</th>";
                            }

                            echo "<th>Contact</th>
                                  <th>Actions</th>
                                </tr>
                              </thead>
                              <tbody>";

                            foreach ($posts as $post) {
                                $statusClass = isset($post['status']) && $post['status'] === 'claimed' ? 'success' : 'warning';
                                $statusText = isset($post['status']) && $post['status'] === 'claimed' ? 'Claimed' : 'Unclaimed';

                                echo "<tr>
                                <td>{$post['id']}</td>
                                <td><span class='user-info'><i class='fas fa-user text-info me-2'></i>{$post['user_name']} <span class='user-email'>({$post['user_email']})</span></span></td>
                                <td><span class='item-title'>{$post['title']}</span></td>
                                <td><img src='../{$post['photo_path']}' alt='Post Photo' class='img-thumbnail' style='max-width: 100px;'></td>
                                <td><i class='fas fa-map-marker-alt text-danger me-2'></i>{$post['location']}</td>
                                <td><span class='date-time-column'><i class='far fa-calendar-alt me-1'></i> {$post['date']} <i class='far fa-clock ms-2 me-1'></i> {$post['time']}</span></td>";

                                // Show type if column exists
                                if (isset($post['type'])) {
                                    $typeText = ucfirst($post['type']);
                                    echo "<td><span class='badge bg-secondary'>{$typeText}</span></td>";
                                }

                                // Show status if column exists
                                if (isset($post['status'])) {
                                    echo "<td><span class='badge bg-{$statusClass}'>{$statusText}</span></td>";
                                }

                                echo "<td><i class='fas fa-phone-alt text-success me-2'></i>{$post['contact_info']}</td>
                                <td>
                                    <button class='btn btn-sm btn-warning mb-1' onclick='editPost({$post['id']})'>Edit</button>
                                    <button class='btn btn-sm btn-danger' onclick='deletePost({$post['id']})'>Delete</button>
                                </td>
                              </tr>";
                            }

                            echo "</tbody></table>";
                            echo "</div>"; // Close table-container
                        } else {
                            echo "<div class='alert alert-info'>No posts found.</div>";
                        }
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>

                    <!-- Edit Post Form (hidden, shown via JS) -->
                    <div id="editPostForm" class="card mt-4 d-none">
                        <div class="card-header bg-warning text-dark">
                            <h5>Edit Post</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="post_id" id="edit_post_id">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_title" class="form-label">Title</label>
                                        <input type="text" name="edit_title" id="edit_title" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_location" class="form-label">Location</label>
                                        <input type="text" name="edit_location" id="edit_location" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_date" class="form-label">Date</label>
                                        <input type="date" name="edit_date" id="edit_date" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_time" class="form-label">Time</label>
                                        <input type="time" name="edit_time" id="edit_time" class="form-control">
                                    </div>
                                </div>

                                <?php
                                // Check if status and type columns exist
                                try {
                                    $statusColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->rowCount() > 0;
                                    $typeColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0;

                                    if ($statusColumnExists || $typeColumnExists) {
                                        echo '<div class="row">';

                                        if ($statusColumnExists) {
                                            echo '<div class="col-md-6 mb-3">
                                                    <label for="edit_status" class="form-label">Status</label>
                                                    <select name="edit_status" id="edit_status" class="form-select">
                                                        <option value="unclaimed">Unclaimed</option>
                                                        <option value="claimed">Claimed</option>
                                                    </select>
                                                </div>';
                                        }

                                        if ($typeColumnExists) {
                                            echo '<div class="col-md-6 mb-3">
                                                    <label for="edit_type" class="form-label">Type</label>
                                                    <select name="edit_type" id="edit_type" class="form-select">
                                                        <option value="lost">Lost</option>
                                                        <option value="found">Found</option>
                                                    </select>
                                                </div>';
                                        }

                                        echo '</div>';
                                    }
                                } catch (Exception $e) {
                                    error_log("Error checking columns: " . $e->getMessage());
                                }
                                ?>

                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea name="edit_description" id="edit_description" class="form-control" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_contact_info" class="form-label">Contact Information</label>
                                    <input type="text" name="edit_contact_info" id="edit_contact_info" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_photo" class="form-label">Photo (leave empty to keep current)</label>
                                    <input type="file" name="edit_photo" id="edit_photo" class="form-control">
                                    <div id="current_photo_preview" class="mt-2"></div>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" name="edit_post" class="btn btn-success">Update Post</button>
                                    <button type="button" class="btn btn-secondary" onclick="hideEditForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- View Post Details Modal -->
                    <div class="modal fade" id="viewPostModal" tabindex="-1" aria-labelledby="viewPostModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-light">
                                    <h5 class="modal-title" id="viewPostModalLabel">Post Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="viewPostBody">
                                    <!-- Content will be loaded via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Manage Users Modal -->
    <div class="modal fade" id="manageUsersModal" tabindex="-1" aria-labelledby="manageUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageUsersModalLabel">Manage Users</h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="editUserForm" class="card mt-4 d-none">
                    <div class="card-header bg-warning text-dark">
                        <h5>Edit User</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <button type="submit" name="edit_user" class="btn btn-success">Update User</button>
                                <button type="button" class="btn btn-secondary" onclick="hideEditUserForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-body">
                    <?php
                    // Connect to users_db
                    $conn = new mysqli("localhost", "root", "", "users_db");
                    if ($conn->connect_error) {
                        echo "<div class='alert alert-danger'>Connection failed: " . $conn->connect_error . "</div>";
                    } else {
                        // Handle user deletion
                        if (isset($_GET['delete_user'])) {
                            $delete_id = intval($_GET['delete_user']);
                            $conn->query("DELETE FROM users WHERE id=$delete_id");
                        }
                        // Handle user update
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
                            $edit_id = intval($_POST['user_id']);
                            $edit_name = $conn->real_escape_string($_POST['edit_name']);
                            $edit_email = $conn->real_escape_string($_POST['edit_email']);
                            $conn->query("UPDATE users SET name='$edit_name', email='$edit_email' WHERE id=$edit_id");
                        }
                        // Fetch users
                        $result = $conn->query("SELECT id, name, email FROM users");
                        echo "<div class='table-container'>";
                        echo "<table class='table table-bordered'><thead class='table-dark'><tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr></thead><tbody>";
                        while ($row = $result->fetch_assoc()) {
                            $is_admin = ($row['email'] === 'admin@gmail.com');

                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>
                                    <button class='btn btn-sm btn-warning' onclick='editUser({$row['id']}, \"{$row['name']}\", \"{$row['email']}\")'>Edit</button>";

                            // Only show delete button if not admin
                            if (!$is_admin) {
                                echo " <a href='?delete_user={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete this user?\")'>Delete</a>";
                            }

                            echo "</td>
                            </tr>";
                        }
                        echo "</tbody></table>";
                        echo "</div>"; // Close table-container
                        $conn->close();
                    }
                    ?>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                    <script>
                        // Function to edit a post
                        function editPost(id) {
                            // First close the view modal if it's open
                            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewPostModal'));
                            if (viewModal) {
                                viewModal.hide();
                            }

                            // Show loading in the edit form
                            document.getElementById('editPostForm').classList.remove('d-none');
                            document.getElementById('edit_post_id').value = id;

                            // Fetch the post data via AJAX
                            fetch(`get_post.php?id=${id}`)
                                .then(response => response.json())
                                .then(post => {
                                    // Fill in the form fields with post data
                                    document.getElementById('edit_title').value = post.title;
                                    document.getElementById('edit_description').value = post.description;
                                    document.getElementById('edit_location').value = post.location;
                                    document.getElementById('edit_date').value = post.date;
                                    document.getElementById('edit_time').value = post.time;
                                    document.getElementById('edit_contact_info').value = post.contact_info;

                                    // Set status and type if they exist
                                    if (post.status && document.getElementById('edit_status')) {
                                        document.getElementById('edit_status').value = post.status;
                                    }
                                    if (post.type && document.getElementById('edit_type')) {
                                        document.getElementById('edit_type').value = post.type;
                                    }

                                    // Show the current photo
                                    if (post.photo_path) {
                                        document.getElementById('current_photo_preview').innerHTML =
                                            `<img src="../${post.photo_path}" alt="Current Photo" class="img-thumbnail" style="max-width: 150px;">
                     <p class="small text-muted mt-1">Current photo (upload a new one to replace)</p>`;
                                    } else {
                                        document.getElementById('current_photo_preview').innerHTML = '<p class="text-muted">No current photo</p>';
                                    }

                                    // Scroll to the edit form
                                    document.getElementById('editPostForm').scrollIntoView({
                                        behavior: "smooth"
                                    });
                                })
                                .catch(error => {
                                    console.error('Error fetching post data:', error);
                                    alert('Error loading post data. Please try again.');
                                    hideEditForm();
                                });
                        }

                        // Function to hide the edit form
                        function hideEditForm() {
                            document.getElementById('editPostForm').classList.add('d-none');
                        }

                        // Function to update item status
                        function updateItemStatus(id, status) {
                            // Show confirmation dialog
                            const action = status === 'claimed' ? 'mark as claimed' : 'mark as unclaimed';
                            if (!confirm(`Are you sure you want to ${action} this item?`)) {
                                return;
                            }

                            // Prepare form data
                            const formData = new FormData();
                            formData.append('post_id', id);
                            formData.append('status', status);

                            // Send request
                            fetch('update_status.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert(data.message);
                                        // Reload the page to show updated stats
                                        location.reload();
                                    } else {
                                        alert('Error: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error updating status:', error);
                                    alert('An error occurred while updating the status');
                                });
                        }

                        // Function to view post details
                        function viewPost(id) {
                            // Show loading indicator
                            const viewPostBody = document.getElementById('viewPostBody');
                            viewPostBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

                            // Show the modal first
                            const viewPostModal = new bootstrap.Modal(document.getElementById('viewPostModal'));
                            viewPostModal.show();

                            // Fetch post data via AJAX
                            fetch(`get_post.php?id=${id}`)
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(post => {
                                    // Create the post details HTML
                                    const postDate = post.date ? `${post.date}` : 'Not specified';
                                    const postTime = post.time ? `${post.time}` : 'Not specified';

                                    // Format date and time nicely
                                    const formattedDateTime = `<span class="date-time-display">
                                        <i class="far fa-calendar-alt text-primary me-2"></i>${postDate}
                                        <i class="far fa-clock text-primary ms-3 me-2"></i>${postTime}
                                    </span>`;

                                    // Format location with icon
                                    const formattedLocation = `<span class="location-display">
                                        <i class="fas fa-map-marker-alt text-danger me-2"></i>${post.location}
                                    </span>`;

                                    // Format contact info with icon
                                    const formattedContact = `<span class="contact-display">
                                        <i class="fas fa-phone-alt text-success me-2"></i>${post.contact_info}
                                    </span>`;

                                    // Determine item status and create appropriate buttons
                                    const statusClass = post.status === 'claimed' ? 'success' : 'warning';
                                    const statusText = post.status === 'claimed' ? 'Claimed' : 'Unclaimed';
                                    const statusButtonText = post.status === 'claimed' ? 'Mark as Unclaimed' : 'Mark as Claimed';
                                    const newStatus = post.status === 'claimed' ? 'unclaimed' : 'claimed';

                                    let detailsHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <img src="../${post.photo_path}" alt="Post Photo" class="img-fluid rounded shadow-sm mb-3">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">${post.title}</h4>
                            <span class="badge bg-${statusClass} p-2">${statusText}</span>
                        </div>
                        <div class="post-meta mb-4">
                            <p class="text-muted mb-3">
                                <i class="fas fa-user-circle me-2"></i>Posted by: <strong>${post.user_email}</strong>
                            </p>
                            <div class="info-grid">
                                <div class="info-item">
                                    <p class="mb-2"><strong>Location:</strong></p>
                                    ${formattedLocation}
                                </div>
                                <div class="info-item">
                                    <p class="mb-2"><strong>Date & Time:</strong></p>
                                    ${formattedDateTime}
                                </div>
                                <div class="info-item">
                                    <p class="mb-2"><strong>Contact Info:</strong></p>
                                    ${formattedContact}
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <i class="fas fa-align-left me-2"></i>Description
                            </div>
                            <div class="card-body">
                                <p>${post.description}</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-warning" onclick="editPost(${post.id})">
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                            <a href="?delete_post=${post.id}" class="btn btn-danger" onclick="return confirm('Delete this post?')">
                                <i class="fas fa-trash me-1"></i> Delete
                            </a>
                            <button class="btn btn-${post.status === 'claimed' ? 'outline-warning' : 'outline-success'}" 
                                    onclick="updateItemStatus(${post.id}, '${newStatus}')">
                                <i class="fas fa-${post.status === 'claimed' ? 'times-circle' : 'check-circle'} me-1"></i>
                                ${statusButtonText}
                            </button>
                        </div>
                    </div>
                </div>
            `;
                                    viewPostBody.innerHTML = detailsHTML;
                                })
                                .catch(error => {
                                    console.error('Error fetching post data:', error);
                                    viewPostBody.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading post data. Please try again.
                </div>`;
                                });
                        }


                        function editUser(id, name, email) {
                            document.getElementById('editUserForm').classList.remove('d-none');
                            document.getElementById('edit_user_id').value = id;
                            document.getElementById('edit_name').value = name;
                            document.getElementById('edit_email').value = email;

                            document.getElementById('editUserForm').scrollIntoView({
                                behavior: "smooth"
                            });
                        }

                        function hideEditUserForm() {
                            document.getElementById('editUserForm').classList.add('d-none');
                        }

                        // Make sure Bootstrap is properly initialized
                        document.addEventListener('DOMContentLoaded', function() {
                            // Initialize all modals
                            var modals = document.querySelectorAll('.modal');
                            modals.forEach(function(modal) {
                                new bootstrap.Modal(modal);
                            });
                        });

                        // Add this new function for handling post deletion
                        function deletePost(id) {
                            if (!confirm('Are you sure you want to delete this post?')) {
                                return;
                            }

                            // Send delete request
                            fetch(`delete_post.php?id=${id}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Remove the row from the table
                                        const row = document.querySelector(`tr[data-post-id="${id}"]`);
                                        if (row) {
                                            row.remove();
                                        }

                                        // Update dashboard statistics
                                        updateDashboardStats();

                                        // Show success message
                                        alert('Post deleted successfully!');
                                    } else {
                                        alert('Error: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error deleting post:', error);
                                    alert('An error occurred while deleting the post');
                                });
                        }

                        // Add this function to update dashboard statistics
                        function updateDashboardStats() {
                            fetch('get_dashboard_stats.php')
                                .then(response => response.json())
                                .then(data => {
                                    // Update the statistics in the dashboard
                                    document.querySelector('.count:nth-child(1)').textContent = data.totalUsers;
                                    document.querySelector('.count:nth-child(2)').textContent = data.totalItems;
                                    document.querySelector('.count:nth-child(3)').textContent = data.claimedItems;
                                    document.querySelector('.count:nth-child(4)').textContent = data.unclaimedItems;
                                })
                                .catch(error => {
                                    console.error('Error updating dashboard stats:', error);
                                });
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['admin_login_success']) && $_SESSION['admin_login_success']): ?>
        <!-- Success Modal -->
        <div class="modal fade" id="adminLoginSuccessModal" tabindex="-1" aria-labelledby="adminLoginSuccessModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center">
                    <div class="modal-body py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3 animated-check"></i>
                        <h4 class="mb-2">Welcome, Admin!</h4>
                        <p class="mb-0">You have successfully logged in to the admin panel.</p>
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Show the modal on page load
            window.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('adminLoginSuccessModal'));
                modal.show();
            });
        </script>
    <?php unset($_SESSION['admin_login_success']);
    endif; ?>

    <!-- Add this modal definition right before the closing </body> tag -->
    <div class="modal fade" id="viewPostModal" tabindex="-1" aria-labelledby="viewPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="viewPostModalLabel">Post Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewPostBody">
                    <!-- Content will be loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update the viewPost function
        function viewPost(id) {
            // Show loading indicator
            const viewPostBody = document.getElementById('viewPostBody');
            viewPostBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            // Show the modal first
            const viewPostModal = new bootstrap.Modal(document.getElementById('viewPostModal'));
            viewPostModal.show();

            // Fetch post data via AJAX
            fetch(`get_post.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(post => {
                    // Create the post details HTML
                    const postDate = post.date ? `${post.date}` : 'Not specified';
                    const postTime = post.time ? `${post.time}` : 'Not specified';

                    // Format date and time nicely
                    const formattedDateTime = `<span class="date-time-display">
                    <i class="far fa-calendar-alt text-primary me-2"></i>${postDate}
                    <i class="far fa-clock text-primary ms-3 me-2"></i>${postTime}
                </span>`;

                    // Format location with icon
                    const formattedLocation = `<span class="location-display">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i>${post.location}
                </span>`;

                    // Format contact info with icon
                    const formattedContact = `<span class="contact-display">
                    <i class="fas fa-phone-alt text-success me-2"></i>${post.contact_info}
                </span>`;

                    // Determine item status and create appropriate buttons
                    const statusClass = post.status === 'claimed' ? 'success' : 'warning';
                    const statusText = post.status === 'claimed' ? 'Claimed' : 'Unclaimed';
                    const statusButtonText = post.status === 'claimed' ? 'Mark as Unclaimed' : 'Mark as Claimed';
                    const newStatus = post.status === 'claimed' ? 'unclaimed' : 'claimed';

                    let detailsHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <img src="../${post.photo_path}" alt="Post Photo" class="img-fluid rounded shadow-sm mb-3">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">${post.title}</h4>
                            <span class="badge bg-${statusClass} p-2">${statusText}</span>
                        </div>
                        <div class="post-meta mb-4">
                            <p class="text-muted mb-3">
                                <i class="fas fa-user-circle me-2"></i>Posted by: <strong>${post.user_email}</strong>
                            </p>
                            <div class="info-grid">
                                <div class="info-item">
                                    <p class="mb-2"><strong>Location:</strong></p>
                                    ${formattedLocation}
                                </div>
                                <div class="info-item">
                                    <p class="mb-2"><strong>Date & Time:</strong></p>
                                    ${formattedDateTime}
                                </div>
                                <div class="info-item">
                                    <p class="mb-2"><strong>Contact Info:</strong></p>
                                    ${formattedContact}
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <i class="fas fa-align-left me-2"></i>Description
                            </div>
                            <div class="card-body">
                                <p>${post.description}</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-warning" onclick="editPost(${post.id})">
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                            <a href="?delete_post=${post.id}" class="btn btn-danger" onclick="return confirm('Delete this post?')">
                                <i class="fas fa-trash me-1"></i> Delete
                            </a>
                            <button class="btn btn-${post.status === 'claimed' ? 'outline-warning' : 'outline-success'}" 
                                    onclick="updateItemStatus(${post.id}, '${newStatus}')">
                                <i class="fas fa-${post.status === 'claimed' ? 'times-circle' : 'check-circle'} me-1"></i>
                                ${statusButtonText}
                            </button>
                        </div>
                    </div>
                </div>
            `;
                    viewPostBody.innerHTML = detailsHTML;
                })
                .catch(error => {
                    console.error('Error fetching post data:', error);
                    viewPostBody.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading post data. Please try again.
                </div>`;
                });
        }

        // Make sure Bootstrap is properly initialized
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all modals
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                new bootstrap.Modal(modal);
            });
        });
    </script>

    <!-- Make sure Bootstrap JS is included -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/js.script"></script>
</body>

</html>