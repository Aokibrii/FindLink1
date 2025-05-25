<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// Check if user is admin
$user_email = $_SESSION['email'];
$is_admin = ($user_email === 'admin@gmail.com');

// Process search
$searchTerm = '';
$sql = "SELECT id, user_email, title as item_name, description, location, date, time, 
        contact_info, photo_path as image_path, type as item_type, created_at, latitude, longitude 
        FROM posts";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['search'])) {
    $searchTerm = trim($_POST['search']);
    header("Location: user_page.php?search=" . urlencode($searchTerm));
    exit();
} elseif (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    if ($searchTerm !== '') {
        $stmt = $conn->prepare($sql . " WHERE title LIKE ? OR description LIKE ? ORDER BY created_at DESC");
        $likeTerm = "%$searchTerm%";
        $stmt->bind_param("ss", $likeTerm, $likeTerm);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql . " ORDER BY created_at DESC LIMIT 6");
    }
} else {
    $result = $conn->query($sql . " ORDER BY created_at DESC LIMIT 6");
}

// Handle AJAX search suggestions
if (isset($_GET['ajax_search']) && isset($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $stmt = $conn->prepare("SELECT id, title, description, type as item_type FROM posts 
                           WHERE title LIKE ? OR description LIKE ? ORDER BY created_at DESC LIMIT 5");
    $likeQ = "%$q%";
    $stmt->bind_param("ss", $likeQ, $likeQ);
    $stmt->execute();
    $res = $stmt->get_result();
    $suggestions = [];
    while ($row = $res->fetch_assoc()) {
        $suggestions[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'item_type' => $row['item_type']
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost and Found</title>
    <link rel="icon" href="images/Icon.jpg">
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/user_page.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/featured-item.css">
    <style>
        /* Sidebar navigation */
        .darkmode .side-nav {
            background-color: #000000;
        }

        .darkmode .side-nav-item {
            color: #ffffff;
        }

        .darkmode .side-nav-item:hover {
            background-color: #2563eb;
        }

        .side-nav {
            width: 70px;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            transition: width 0.3s ease;
        }

        .side-nav.expanded {
            width: 200px;
        }

        .side-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 70px;
            width: 70px;
            padding: 0;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .side-nav.expanded .side-nav-item {
            width: 100%;
            flex-direction: row;
            justify-content: flex-start;
            padding-left: 25px;
        }

        .side-nav-item span {
            font-size: 10px;
            margin-top: 3px;
            white-space: nowrap;
            transition: opacity 0.2s ease;
        }

        .side-nav.expanded .side-nav-item span {
            font-size: 14px;
            margin-top: 0;
            margin-left: 15px;
            opacity: 1;
        }

        .side-nav-item.active {
            color: #fff;
            background-color: #3b82f6;
        }

        .side-nav-item:hover {
            background-color: #f1f5f9;
            color: #3b82f6;
        }

        .side-nav-item.active:hover {
            background-color: #2563eb;
            color: #fff;
        }

        .side-nav-item i {
            font-size: 20px;
            margin-bottom: 5px;
            transition: margin 0.3s ease;
        }

        .side-nav.expanded .side-nav-item i {
            margin-bottom: 0;
        }

        main {
            margin-left: 70px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        main.nav-expanded {
            margin-left: 200px;
        }

        .toggle-btn {
            width: 40px;
            height: 40px;
            background-color: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: absolute;
            bottom: 20px;
            left: 15px;
            cursor: pointer;
            transition: transform 0.3s ease, background-color 0.3s ease;
            z-index: 1001;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2);
        }

        .toggle-btn:hover {
            background-color: #2563eb;
            transform: scale(1.05);
        }

        .side-nav.expanded .toggle-btn {
            transform: rotate(180deg);
        }

        /* Login success modal */
        .login-success-modal .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }

        .login-success-icon {
            background-color: #3b82f6;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .continue-btn {
            background-color: #3b82f6;
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            margin-top: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .continue-btn:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.2);
        }

        /* Enhanced Search Styles */
        .search-container {
            position: relative;
            transition: all 0.4s ease;
            margin: 0;
            width: auto;

        }

        .search-container.collapsed {
            width: 50px;
            overflow: hidden;
        }

        .search-container.expanded {
            width: 350px;

        }

        .search-icon-btn {
            background-color: #3b82f6;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: absolute;
            top: 20px;
            left: 0;
            z-index: 1002;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.15);
        }

        .search-icon-btn:hover {
            background-color: #2563eb;
            transform: scale(1.05);
        }

        .search-container.expanded .search-icon-btn {
            background-color: #2563eb;
            transform: rotate(90deg);
            left: 0;
        }

        .search-filter {
            position: relative;
            width: 100%;
            padding-left: 53px;
            opacity: 0;
            transition: opacity 0.3s ease;
            top: 20px;
        }

        .search-container.expanded .search-filter {
            opacity: 1;

        }

        .search-filter input {
            width: 100%;
            padding: 14px 45px 14px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .search-filter input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .search-filter .clear-btn {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            font-size: 16px;
            display: none;
            transition: color 0.2s ease;
        }

        .search-filter .clear-btn:hover {
            color: #64748b;
        }

        .search-filter input:not(:placeholder-shown)~.clear-btn {
            display: block;
        }

        #suggestions {
            position: absolute;
            top: 48px;
            left: 53px;
            right: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            border: 1px solid #e2e8f0;
        }

        .suggestion {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .suggestion:hover {
            background-color: #f8fafc;
        }

        .suggestion .item-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
            color: white;
            background-color: #3b82f6;
            font-weight: 500;
        }

        .suggestion .item-type.Found {
            background-color: #10b981;
        }

        .suggestion .item-type.Lost {
            background-color: #ef4444;
        }

        .no-suggestions {
            padding: 15px;
            color: #6b7280;
            text-align: center;
            font-style: italic;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #4b5563;
            font-weight: 500;
        }

        .filter-btn.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .filter-btn:hover:not(.active) {
            background-color: #f1f5f9;
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .item-cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
        }

        .item-card {
            width: calc(33.33% - 20px);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }

        .item-img-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .item-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .item-card:hover .item-img {
            transform: scale(1.1);
        }

        .item-info {
            width: 100%;
        }

        .item-info h2 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #1f2937;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .item-info p {
            margin-bottom: 10px;
            color: #4b5563;
            line-height: 1.5;
        }

        .item-meta {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
            color: #6b7280;
            font-size: 14px;
        }

        .item-meta i {
            margin-right: 5px;
            color: #3b82f6;
        }

        .item-footer {
            width: 100%;
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }

        .contact-info {
            display: flex;
            align-items: center;
            color: #4b5563;
        }

        .contact-info i {
            margin-right: 5px;
            color: #3b82f6;
        }

        .contact-info span {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .no-items {
            text-align: center;
            padding: 50px;
            color: #777;
            font-style: italic;
        }

        .no-items i {
            font-size: 40px;
            margin-bottom: 20px;
        }

        .post-item-btn {
            background-color: #4e73f8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .post-item-btn:hover {
            background-color: #3b5de7;
        }
    </style>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('ServiceWorker registration successful'))
                    .catch(err => console.log('ServiceWorker registration failed: ', err));
            });
        }
    </script>
</head>

<body>
    <!-- Login Success Modal -->
    <?php if (isset($_SESSION['user_login_success']) && $_SESSION['user_login_success']): ?>
        <div class="modal fade login-success-modal" id="userLoginSuccessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center">
                    <div class="modal-body py-4">
                        <div class="login-success-icon">
                            <i class="fas fa-check text-white fa-2x"></i>
                        </div>
                        <h4 class="mb-3" style="font-weight: 600; color: #444;">Welcome <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>!</h4>
                        <p class="mb-3" style="color: #666;">You have successfully logged in.</p>
                        <button type="button" class="btn btn-primary continue-btn" data-bs-dismiss="modal">Continue</button>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['user_login_success']); ?>
    <?php endif; ?>

    <div class="header">
        <!-- Sidebar Navigation -->
        <div class="side-nav" id="sideNav">
            <a href="User_page.php" class="logo">
                <img src="images/Icon.jpg" class="logo-img">
            </a>
            <ul class="nav-links">
                <li>
                    <a href="User_page.php" class="side-nav-item active">
                        <i class="fa-solid fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>

                <li>
                    <a href="notifications.php" class="side-nav-item">
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

        <!-- Main content area -->
        <div id="mainContent">
            <!-- Logout Confirmation Modal -->
            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center">
                        <div class="modal-body py-5">
                            <i class="fas fa-sign-out-alt fa-4x text-danger mb-3"></i>
                            <h4 class="mb-2" id="logoutModalLabel">Ready to leave?</h4>
                            <p class="mb-4">Are you sure you want to logout?</p>
                            <div class="d-flex justify-content-center gap-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <a href="logout.php" class="btn btn-danger">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="featured-items-section">
                <div class="featured-header">
                    <div class="featured-item-text">
                        <h1>Find Link</h1>
                        <div class="welcome-subtitle">
                            <p>Lost something? Found something? Let's reconnect people with their precious belongings.</p>
                            <p class="user-greeting">We are here to help you, <span class="username"><?= $_SESSION['name'] ?? 'User' ?></span>.</p>
                        </div>
                    </div>
                </div>

                <!-- Header Actions Container -->
                <div class="header-actions">
                    <!-- Filter controls -->
                    <div class="filter-controls">
                        <button class="filter-btn active" data-filter="all">All Items</button>
                        <button class="filter-btn lost-filter" data-filter="lost">Lost Items</button>
                        <button class="filter-btn found-filter" data-filter="found">Found Items</button>
                    </div>
                    <div class="search-container collapsed" id="searchContainer">
                        <div class="search-icon-btn" id="searchToggle">
                            <i class="fa fa-search"></i>
                        </div>
                        <form method="POST" class="search-filter" id="searchForm">
                            <input type="text" name="search" id="searchInput" placeholder="Search for items..."
                                value="<?= htmlspecialchars($searchTerm) ?>" autocomplete="off" onkeyup="showSuggestions(this.value)">
                            <button type="button" class="clear-btn" id="clearSearch">
                                <i class="fa fa-times"></i>
                            </button>
                            <button type="submit" style="display:none"></button>
                            <div id="suggestions"></div>
                        </form>
                    </div>
                    <!-- Add Post New Item button -->
                    <div class="post-item-btn-container">
                        <a href="Post_Lost_and_Found.php" class="post-item-btn">
                            <i class="fa-solid fa-plus"></i> Post New Item
                        </a>
                    </div>
                </div>

                <div class="item-cards-container">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $itemId = $row['id'] ?? 0;
                            $itemName = $row['item_name'] ?? 'Unknown Item';
                            $description = $row['description'] ?? 'No description available';
                            $contact = $row['contact_info'] ?? 'No contact information';
                            $imagePath = $row['image_path'] ?? 'images/default-item.jpg';
                            $itemType = $row['item_type'] ?? 'Unknown';
                            $location = $row['location'] ?? 'Unknown location';
                            $date = $row['date'] ?? date('Y-m-d');

                            // Check if image exists
                            if (!file_exists($imagePath)) {
                                $imagePath = 'images/default-item.jpg';
                            }

                            // Format date
                            $formattedDate = date('M d, Y', strtotime($date));
                        ?>
                            <div class="item-card" data-type="<?= strtolower($itemType) ?>">
                                <div class="item-img-container">
                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($itemName) ?>" class="item-img" loading="lazy">
                                    <div class="item-type-badge <?= htmlspecialchars($itemType) ?>">
                                        <?php if ($itemType === 'Lost'): ?>
                                            <i class="fas fa-search"></i>
                                        <?php elseif ($itemType === 'Found'): ?>
                                            <i class="fas fa-check"></i>
                                        <?php elseif ($itemType === 'Returned'): ?>
                                            <i class="fas fa-exchange-alt"></i>
                                        <?php elseif ($itemType === 'Claimed'): ?>
                                            <i class="fas fa-hand-paper"></i>
                                        <?php else: ?>
                                            <i class="fas fa-question"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($itemType) ?>
                                    </div>
                                </div>
                                <div class="item-info">
                                    <h2 class="item-title"><?= htmlspecialchars($itemName) ?></h2>
                                    <p class="item-description"><?= htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : '') ?></p>
                                    <div class="item-meta">
                                        <div class="item-location" title="<?= htmlspecialchars($location) ?>">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <?= htmlspecialchars(substr($location, 0, 15)) . (strlen($location) > 15 ? '...' : '') ?>
                                        </div>
                                        <div class="item-date">
                                            <i class="fa-solid fa-calendar"></i>
                                            <?= htmlspecialchars($formattedDate) ?>
                                        </div>
                                    </div>
                                    <a href="item_details.php?id=<?= $itemId ?>" class="view-details-btn">View Details</a>
                                </div>
                                <div class="item-footer">
                                    <div class="contact-info" title="<?= htmlspecialchars($contact) ?>">
                                        <i class="fa-solid fa-phone"></i>
                                        <span><?= htmlspecialchars(substr($contact, 0, 20)) . (strlen($contact) > 20 ? '...' : '') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class='no-items'>
                            <i class="fa-solid fa-search"></i>
                            <p>No items found. Be the first to post a lost or found item!</p>
                            <a href="Post_Lost_and_Found.php" class="post-item-btn">
                                <i class="fa-solid fa-plus"></i> Post an Item
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Include JavaScript files -->
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar.js"></script>
    <script src="js/featured-items.js"></script>
    <script src="js/search.js"></script>
</body>

</html>
<?php $conn->close(); ?>