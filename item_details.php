<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; // Change to your DB username
$password = ""; // Change to your DB password
$dbname = "user_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get item ID from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id <= 0) {
    header("Location: User_page.php");
    exit();
}


$sql = "SELECT id, user_email, title as item_name, description, location, date, time, 
        contact_info, photo_path as image_path, 
        type as item_type, 
        created_at, latitude, longitude 
        FROM posts 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Item not found
    header("Location: User_page.php");
    exit();
}

$item = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']) ?> | Lost and Found</title>
    <link rel="icon" href="images/Icon.jpg">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/user_page.css">
    <link rel="stylesheet" href="css/item_details.css">
    <style>
        /* Sidebar navigation */
        .side-nav {
            width: 70px;
            background-color: #f8f9fa;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
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
            color: #666;
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
            background-color: #4e73f8;
        }

        .side-nav-item:hover {
            background-color: #e9ecef;
            color: #4e73f8;
        }

        .side-nav-item.active:hover {
            background-color: #4e73f8;
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
            background-color: #4e73f8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: absolute;
            bottom: 20px;
            left: 15px;
            cursor: pointer;
            transition: transform 0.3s ease;
            z-index: 1001;
        }

        .side-nav.expanded .toggle-btn {
            transform: rotate(180deg);
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="side-nav" id="sideNav">
            <a href="User_page.php" class="logo" style="display: flex; justify-content: center; margin: 15px 0;">
                <img src="images/Icon.jpg" class="logo-img" style="width: 40px; height: 40px;">
            </a>
            <ul class="nav-links" style="padding: 0; margin: 0;">
                <li style="margin: 0;">
                    <a href="User_page.php" class="side-nav-item">
                        <i class="fa-solid fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>


                <li style="margin: 0;">
                    <a href="messages.php" class="side-nav-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </li>
                <li style="margin: 0;">
                    <a href="Profile.php" class="side-nav-item">
                        <i class="fa-solid fa-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
            <!-- Toggle button for hamburger menu -->
            <div class="toggle-btn" id="toggleNav">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>

        <main>
            <div class="item-details-container">
                <div class="back-button">
                    <a href="javascript:history.back()"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>

                <div class="item-header">
                    <h1><?= htmlspecialchars($item['item_name']) ?></h1>
                    <span class="item-badge <?= strtolower($item['item_type']) ?>-badge"><?= htmlspecialchars($item['item_type']) ?></span>
                </div>

                <div class="item-content">
                    <div class="item-image">
                        <?php
                        $imagePath = $item['image_path'] ?? 'images/default-item.jpg';
                        if (!file_exists($imagePath)) {
                            $imagePath = 'images/default-item.jpg';
                        }
                        ?>
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                        <div class="item-status">
                            <span class="status-badge <?= strtolower($item['item_type']) ?>">
                                <i class="fa-solid <?= $item['item_type'] === 'Lost' ? 'fa-question-circle' : 'fa-check-circle' ?>"></i>
                                <?= htmlspecialchars($item['item_type']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="item-info">
                        <div class="info-section">
                            <h3>Description</h3>
                            <p><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                        </div>

                        <div class="info-section">
                            <h3>Details</h3>
                            <ul class="details-list">
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    <span>
                                        <strong>Location:</strong>
                                        <?= htmlspecialchars($item['location']) ?>
                                        <?php if (!empty($item['latitude']) && !empty($item['longitude'])): ?>
                                            <a href="https://www.google.com/maps?q=<?= $item['latitude'] ?>,<?= $item['longitude'] ?>" target="_blank" class="map-link">
                                                <i class="fa-solid fa-map-location-dot"></i> View on Map
                                            </a>
                                        <?php endif; ?>
                                    </span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-calendar"></i>
                                    <span><strong>Date:</strong> <?= htmlspecialchars(date('F d, Y', strtotime($item['date']))) ?></span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock"></i>
                                    <span><strong>Time:</strong> <?= htmlspecialchars($item['time']) ?></span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-phone"></i>
                                    <span><strong>Contact:</strong> <?= htmlspecialchars($item['contact_info']) ?></span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-user"></i>
                                    <span><strong>Posted by:</strong> <?= htmlspecialchars($item['user_email']) ?></span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span><strong>Posted on:</strong> <?= htmlspecialchars(date('F d, Y \a\t h:i A', strtotime($item['created_at']))) ?></span>
                                </li>
                            </ul>
                        </div>

                        <div class="contact-section">
                            <?php if (!empty($item['contact_info'])): ?>
                                <a href="tel:<?= htmlspecialchars($item['contact_info']) ?>" class="contact-btn">
                                    <i class="fa-solid fa-phone"></i> Call Owner
                                </a>
                            <?php endif; ?>
                            <a href="mailto:<?= htmlspecialchars($item['user_email']) ?>" class="contact-btn">
                                <i class="fa-solid fa-envelope"></i> Email Owner
                            </a>
                        </div>

                        <div class="social-sharing">
                            <h3>Share this item</h3>
                            <div class="social-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" target="_blank" class="social-btn facebook">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                                <a href="https://www.facebook.com/dialog/send?link=<?= urlencode($_SERVER['REQUEST_URI']) ?>&app_id=YOUR_APP_ID&redirect_uri=<?= urlencode($_SERVER['REQUEST_URI']) ?>" target="_blank" class="social-btn messenger">
                                    <i class="fa-brands fa-facebook-messenger"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if (!empty($item['latitude']) && !empty($item['longitude'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mapElement = document.getElementById('map');
                const lat = parseFloat(mapElement.dataset.lat);
                const lng = parseFloat(mapElement.dataset.lng);

                // Initialize map here using coordinates
                // This is a placeholder - you would implement this with your preferred mapping API
                console.log(`Map should display coordinates: ${lat}, ${lng}`);

                // Example for integration with mapping API like Google Maps or Leaflet
                // initMap(lat, lng);
            });
        </script>
    <?php endif; ?>

    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle hamburger navigation
        document.addEventListener('DOMContentLoaded', function() {
            const sideNav = document.getElementById('sideNav');
            const mainContent = document.querySelector('main');
            const toggleBtn = document.getElementById('toggleNav');

            // Check if navigation state is saved in localStorage
            const navExpanded = localStorage.getItem('navExpanded') === 'true';
            if (navExpanded) {
                sideNav.classList.add('expanded');
                mainContent.classList.add('nav-expanded');
            }

            toggleBtn.addEventListener('click', function() {
                sideNav.classList.toggle('expanded');
                mainContent.classList.toggle('nav-expanded');

                // Save navigation state to localStorage
                localStorage.setItem('navExpanded', sideNav.classList.contains('expanded'));
            });
        });
    </script>
    <script src="js/script.js"></script>
</body>

</html>

<?php
$conn->close();
?>