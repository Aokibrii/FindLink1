<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    $profile_img = $_SESSION['profile_img']; // Default to current image

    // Handle file upload
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
        $img_name = basename($_FILES['profile_img']['name']);
        $target_dir = "uploads/";
        $target_file = $target_dir . uniqid() . "_" . $img_name;
        if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $target_file)) {
            $profile_img = basename($target_file);
        }
    }

    // Update user in database
    $stmt = $connect->prepare("UPDATE users SET name=?, email=?, profile_img=? WHERE email=?");
    $stmt->bind_param("ssss", $new_name, $new_email, $profile_img, $email);
    $stmt->execute();
    $stmt->close();

    // Update session and redirect
    $_SESSION['name'] = $new_name;
    $_SESSION['email'] = $new_email;
    $_SESSION['profile_img'] = $profile_img;
    header("Location: Profile.php");
    exit();
}

// Always fetch the latest user data from the database
$stmt = $connect->prepare("SELECT name, email, profile_img FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($name, $email, $profile_img);
if ($stmt->fetch()) {
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['profile_img'] = $profile_img;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="icon" href="images/Icon.jpg">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/user_page.css">
    <link rel="stylesheet" href="css/Profile.css">
</head>

<body>
    <div class="header">
        <!-- Sidebar Navigation -->
        <div class="side-nav" id="sideNav">
            <a href="User_page.php" class="logo">
                <img src="images/Icon.jpg" class="logo-img">
            </a>
            <ul class="nav-links">
                <li>
                    <a href="User_page.php" class="side-nav-item">
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
                    <a href="Profile.php" class="side-nav-item active">
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
            <div class="user-profile">
                <h2>User Profile</h2>
                <div class="profile-info">
                    <img src="<?php
                                if (isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img'])) {
                                    echo 'uploads/' . $_SESSION['profile_img'];
                                } else {
                                    echo 'images/default-profile-pic.png'; // Path to default profile image
                                }
                                ?>" alt="Profile Picture" class="profile-pic">
                    <div class="profile-details">
                        <h3><?php echo $_SESSION['name']; ?></h3>
                        <p>Email: <?php echo $_SESSION['email']; ?></p>
                    </div>
                    <div class="profile-actions">
                        <button type="button" class="edit-btn" onclick="openEditModal()">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                        </button>
                        <button type="button" class="logout-btn" onclick="confirmLogout()">
                            <i class="fa-solid fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div id="editModal">
                <div>
                    <h3>Edit Profile</h3>
                    <form id="editProfileForm" method="post" action="Profile.php" enctype="multipart/form-data">
                        <label>Name:</label>
                        <input type="text" name="name" value="<?php echo $_SESSION['name']; ?>" required>

                        <label>Email:</label>
                        <input type="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>

                        <label>Profile Photo:</label>
                        <input type="file" class="choose-file" name="profile_img" accept="image/*">

                        <div class="profile-img-preview">
                            <p>Current profile image:</p>
                            <img src="<?php
                                        if (isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img'])) {
                                            echo 'uploads/' . $_SESSION['profile_img'];
                                        } else {
                                            echo 'images/default-profile-pic.png';
                                        }
                                        ?>" alt="Profile Preview" id="profilePreviewImage">
                        </div>

                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit">Save</button>
                            <button type="button" onclick="closeEditModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logout Confirmation Modal -->
            <div id="logout-modal">
                <div class="modal-content">
                    <h3>Logout Confirmation</h3>
                    <p>Are you sure you want to logout from your account?</p>
                    <div class="modal-buttons">
                        <button id="logout-proceed-btn">Yes, Logout</button>
                        <button id="logout-cancel-btn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript files -->
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar.js"></script>
    <script src="js/profile.js"></script>
</body>

</html>