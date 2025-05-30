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
    <title>Findlink ADMIN</title>
    <link rel="icon" href="../images/Icon.jpg">
    <link rel="stylesheet" href="../vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_page.css">
    <!-- Add no-cache headers to prevent browser caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        /* Essential Layout Styles Only - Clean & Simple */
        body {
            display: flex;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 70px;
            height: 100vh;
            background-color: var(--dark-color);
            z-index: 1002;
            box-shadow: var(--shadow-md);
            transition: width 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.expanded {
            width: 250px;
        }

        .sidebar-header {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid var(--gray-600);
        }

        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .sidebar ul {
            list-style: none;
            padding: 1rem 0.5rem;
            margin: 0;
        }

        .sidebar li {
            margin: 0.25rem 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
            border-radius: 6px;
            font-weight: 500;
        }

        .sidebar a:hover {
            background-color: var(--gray-600);
            color: var(--light-color);
        }

        .sidebar a i {
            font-size: 18px;
            width: 32px;
            text-align: center;
            color: var(--gray-400);
        }

        .sidebar a span {
            margin-left: 0.75rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar.expanded a span {
            opacity: 1;
        }

        .sidebar a.active {
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar a.active i {
            color: white;
        }

        /* Main Content Layout */
        .main-content {
            flex: 1;
            margin-left: 70px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 250px;
        }

        /* Dashboard Statistics Cards */
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--primary-color);
        }

        .stat-card .count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .stat-card .label {
            color: var(--text-light);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggle {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            width: 48px;
            height: 48px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            cursor: pointer;
            z-index: 1003;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .sidebar-toggle:hover {
            background-color: var(--primary-dark);
            transform: scale(1.05);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 250px;
            }

            .sidebar.expanded {
                transform: translateX(0);
            }
        }

        @media (max-width: 576px) {
            .sidebar-toggle {
                bottom: 1rem;
                left: 1rem;
                width: 44px;
                height: 44px;
            }

            .main-content {
                padding: 1rem 0.75rem;
            }
        }

        /* Clean Main Content */
        .welcome-section {
            padding: 2rem;
            text-align: center;
            background-color: #3b82f6;
            color: white;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .welcome-section h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Clean Dashboard Statistics Cards */
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: #3b82f6;
        }

        .stat-card .count {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .stat-card .label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        /* Clean Table Container */
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .table {
            margin-bottom: 0;
            width: 100%;
        }

        .table thead th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
            padding: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table tbody td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        /* Simple Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-direction: column;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        .btn i {
            margin-right: 0.375rem;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            color: white;
        }

        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background-color: #d97706;
            color: white;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            color: white;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
            color: white;
        }

        .btn-light {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-light:hover {
            background-color: #f1f5f9;
            color: #334155;
        }

        /* Simple Status Badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .badge.bg-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge.bg-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge.bg-secondary {
            background-color: #f1f5f9;
            color: rgb(255, 255, 255);
        }

        /* Clean Scrollbar */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Clean Card Styles */
        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #1e293b;
            color: white;
            border-radius: 8px 8px 0 0;
            padding: 1rem;
            border-bottom: none;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Clean Modal Styles */
        .modal-content {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background-color: #f8fafc;
            color: #1e293b;
            border-radius: 8px 8px 0 0;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            background-color: #f8fafc;
            border-radius: 0 0 8px 8px;
            border-top: 1px solid #e2e8f0;
            padding: 1rem;
        }

        /* Clean Image Thumbnails */
        .img-thumbnail {
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            padding: 0.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            background: white;
            transition: all 0.2s ease;
        }

        .img-thumbnail:hover {
            border-color: #3b82f6;
            transform: scale(1.02);
        }

        /* Clean Form Styles */
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* Clean Alert Styles */
        .alert {
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-color: #bfdbfe;
        }

        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }

        /* Utility Classes */
        .text-center {
            text-align: center;
        }

        .d-none {
            display: none !important;
        }

        .d-flex {
            display: flex !important;
        }

        .justify-content-between {
            justify-content: space-between !important;
        }

        .align-items-center {
            align-items: center !important;
        }

        .mb-0 {
            margin-bottom: 0 !important;
        }

        .mb-1 {
            margin-bottom: 0.25rem !important;
        }

        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mb-4 {
            margin-bottom: 1rem !important;
        }

        .me-2 {
            margin-right: 0.5rem !important;
        }

        .ms-2 {
            margin-left: 0.5rem !important;
        }

        .g-4>* {
            padding: 1rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -1rem;
        }

        .col-md-3 {
            flex: 0 0 auto;
            width: 25%;
        }

        @media (max-width: 768px) {
            .col-md-3 {
                width: 50%;
            }
        }

        @media (max-width: 576px) {
            .col-md-3 {
                width: 100%;
            }
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
                    <div class="stat-card text-center">
                        <i class="fas fa-users"></i>
                        <div class="count"><?php echo $totalUsers; ?></div>
                        <div class="label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-box-open"></i>
                        <div class="count"><?php echo $totalItems; ?></div>
                        <div class="label">Total Items</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-check-circle"></i>
                        <div class="count"><?php echo $claimedItems; ?></div>
                        <div class="label">Claimed Items</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="count"><?php echo $unclaimedItems; ?></div>
                        <div class="label">Unclaimed Items</div>
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