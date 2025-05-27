<?php
session_start();
// Add caching headers for better performance
header("Cache-Control: max-age=31536000, public");
// Compress output if possible
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', '5');
}

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$host = 'localhost';
$db   = 'user_db'; // Fixed database name
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $contact_info = $_POST['contact_info'] ?? '';
    $user_email = $_SESSION['email'] ?? '';
    $photo_path = '';

    if (empty($type)) {
        $errors[] = "Type (Lost/Found) is required.";
    }

    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (empty($contact_info)) {
        $errors[] = "Contact information is required.";
    }

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = basename($_FILES['photo']['name']);
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed.";
        } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "File size should not exceed 5MB.";
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $destPath = $uploadDir . uniqid() . '_' . $fileName;

            // Optimize image before saving
            $optimized = false;
            if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
                // For JPEG images
                if ($fileType == 'image/jpeg') {
                    $image = imagecreatefromjpeg($fileTmpPath);
                    if ($image) {
                        // Compress with 80% quality
                        imagejpeg($image, $destPath, 80);
                        imagedestroy($image);
                        $optimized = true;
                    }
                }
                // For PNG images
                elseif ($fileType == 'image/png' && function_exists('imagecreatefrompng') && function_exists('imagepng')) {
                    $image = imagecreatefrompng($fileTmpPath);
                    if ($image) {
                        // Compress with higher compression level (lower quality)
                        imagepng($image, $destPath, 8); // Compression level 0-9, 9 being highest compression
                        imagedestroy($image);
                        $optimized = true;
                    }
                }
            }

            // If optimization failed or not applicable, use regular upload
            if (!$optimized) {
                if (!move_uploaded_file($fileTmpPath, $destPath)) {
                    $errors[] = "Failed to upload the photo.";
                }
            }

            $photo_path = $destPath;
        }
    } else {
        $errors[] = "Photo is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_email, type, title, description, location, latitude, longitude, date, time, contact_info, photo_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_email, $type, $title, $description, $location, $latitude, $longitude, $date, $time, $contact_info, $photo_path]);
        header("Location: user_page.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost and Found</title>
    <link rel="icon" href="images/Icon.jpg">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/user_page.css">
    <link rel="stylesheet" href="css/post.css">
    <!-- Only load Leaflet CSS when needed -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" media="print" onload="this.media='all'">
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

        /* Update the center-card-container to adjust with sidebar width */
        .center-card-container {
            left: 70px;
            width: calc(100% - 70px);
            transition: all 0.3s ease;
        }

        .center-card-container.nav-expanded {
            left: 200px;
            width: calc(100% - 200px);
        }

        /* Map Modal Styles */
        #mapModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1010;
            align-items: center;
            justify-content: center;
        }

        #mapModal>div {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            max-width: 95vw;
            max-height: 95vh;
            position: relative;
            min-width: 320px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        #leafletMap {
            width: 500px;
            height: 350px;
            max-width: 90vw;
            max-height: 60vh;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        /* Geocoding search styles */
        .geocoding-container {
            margin-bottom: 15px;
            position: relative;
        }

        .geocoding-container input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .geocoding-container input:focus {
            outline: none;
            border-color: #32a8cc;
            box-shadow: 0 0 0 3px rgba(50, 168, 204, 0.15);
        }

        .geocoding-container button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #32a8cc;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
        }

        .geocoding-container button:hover {
            color: #248193;
        }

        .geocoding-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 6px 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1020;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .geocoding-results.active {
            display: block;
        }

        .geocoding-result-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .geocoding-result-item:hover {
            background: #f0f7ff;
        }

        .geocoding-result-item:last-child {
            border-bottom: none;
        }

        .location-coordinates {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 600px) {
            #leafletMap {
                width: 90vw;
                height: 40vh;
            }
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

        /* Post Item Button */
        .post-item-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 20px;
            background: linear-gradient(135deg, #4e73f8, #3b5de7);
            color: white;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 6px 12px rgba(78, 115, 248, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* Form indicator for progress */
        .form-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
            max-width: 70%;
        }

        .form-progress::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }

        .progress-step {
            position: relative;
            z-index: 2;
            background: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e0e0e0;
            font-weight: bold;
            color: #888;
        }

        .progress-step.active {
            border-color: #32a8cc;
            color: #32a8cc;
            background: #e6f7fb;
        }

        .progress-step.completed {
            border-color: #22c55e;
            background: #22c55e;
            color: white;
        }

        /* Form section controls */
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form navigation buttons */
        .form-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            width: 70%;
        }

        .form-nav button {
            background: #f0f0f0;
            border: none;
            padding: 8px 18px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .form-nav button:hover {
            background: #e0e0e0;
        }

        .form-nav button.next {
            background: #32a8cc;
            color: white;
        }

        .form-nav button.next:hover {
            background: #248193;
        }

        /* Character counter styling */
        .char-counter {
            color: #666;
            font-size: 0.8rem;
            text-align: right;
            margin-top: 2px;
            width: 70%;
        }
    </style>
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

        <!-- Main content -->
        <div id="mainContent">
            <div class="landscape-card">
                <div class="form-side">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <a href="User_page.php" class="back-button" style="margin-right: 15px; color: #4e73f8;">
                            <i class="fa-solid fa-arrow-left" style="font-size: 20px;"></i>
                        </a>
                        <h2>POST LOST AND FOUND ITEM</h2>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div style="color:red; background-color: rgba(255,0,0,0.1); padding: 10px; border-radius: 5px; margin-bottom: 15px; width: 70%;">
                            <?php foreach ($errors as $error) echo "<p style='margin-bottom: 5px;'><i class='fas fa-exclamation-circle'></i> $error</p>"; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div style="color:green; background-color: rgba(0,255,0,0.1); padding: 10px; border-radius: 5px; margin-bottom: 15px; width: 70%;">
                            <p style='margin-bottom: 5px;'><i class='fas fa-check-circle'></i> <?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Form progress indicator -->
                    <div class="form-progress">
                        <div class="progress-step active" data-step="1">1</div>
                        <div class="progress-step" data-step="2">2</div>
                        <div class="progress-step" data-step="3">3</div>
                    </div>

                    <form id="postForm" action="" method="post" enctype="multipart/form-data" autocomplete="off">
                        <!-- Section 1: Basic Information -->
                        <div class="form-section active" data-section="1">
                            <label class="form-label">Type <span class="required">*</span></label>
                            <select name="type" class="input-modern input-type" required>
                                <option value="">Select type</option>
                                <option value="Lost">Lost</option>
                                <option value="Found">Found</option>
                            </select>

                            <label class="form-label">Title <span class="required">*</span></label>
                            <input type="text" name="title" class="input-modern input-title" maxlength="30" required placeholder="Title (e.g. Lost Wallet or Found Wallet)" />
                            <div class="char-counter">0/30</div>

                            <label class="form-label">Description <span class="required">*</span></label>
                            <textarea name="description" class="input-modern" maxlength="50" required placeholder="Describe the item..."></textarea>
                            <div class="char-counter">0/50</div>

                            <div class="form-nav">
                                <div></div> <!-- Empty div for spacing -->
                                <button type="button" class="next" data-next="2">Next <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>

                        <!-- Section 2: Date, Time & Location -->
                        <div class="form-section" data-section="2">
                            <div class="input-row-container">
                                <div class="input-row">
                                    <div>
                                        <label class="form-label">Time <span class="required">*</span></label>
                                        <input type="time" name="time" class="input-modern" required>
                                        <div class="helper-text">Hour Minutes</div>
                                    </div>
                                    <div>
                                        <label class="form-label">Date <span class="required">*</span></label>
                                        <input type="date" name="date" class="input-modern" required>
                                        <div class="helper-text">Date</div>
                                    </div>
                                </div>
                            </div>

                            <label class="form-label">Location <span class="required">*</span></label>
                            <input type="text" name="location" id="locationInput" class="input-location" required placeholder="Street Address" readonly style="background:#f9f9f9;cursor:pointer;">
                            <input type="hidden" name="latitude" id="latitudeInput">
                            <input type="hidden" name="longitude" id="longitudeInput">
                            <button type="button" id="openMapBtn" style="margin-bottom:10px;">Select Location</button>

                            <div class="form-nav">
                                <button type="button" class="prev" data-prev="1"><i class="fas fa-arrow-left"></i> Previous</button>
                                <button type="button" class="next" data-next="3">Next <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>

                        <!-- Section 3: Contact & Photos -->
                        <div class="form-section" data-section="3">
                            <!-- Contact Information Field -->
                            <label class="form-label">Contact Information <span class="required">*</span></label>
                            <input type="text" name="contact_info" class="input-modern" style="width: 70%;" required placeholder="Phone number or other contact information">
                            <div class="helper-text">How others can reach you</div>

                            <label class="form-label">Upload Photos <span class="required">*</span></label>
                            <div class="upload-area">
                                <span>
                                    <i class="fa fa-upload" style="font-size:1.5em;"></i><br>
                                    Upload a File and Photos<br>
                                    <span style="font-weight:400;font-size:0.95em;">Drag and drop files here</span>
                                </span>
                                <input type="file" name="photo" id="photoInput" accept="image/*" required>
                            </div>

                            <div class="form-nav">
                                <button type="button" class="prev" data-prev="2"><i class="fas fa-arrow-left"></i> Previous</button>
                                <button type="submit">POST</button>
                            </div>
                        </div>

                        <!-- Map Picker Modal -->
                        <div id="mapModal">
                            <div>
                                <button id="closeMapBtn" type="button" style="position:absolute;top:10px;right:10px;font-size:18px; background:none; border:none; cursor:pointer;">&times;</button>
                                <h3>Pick Location</h3>

                                <!-- Connection status indicator -->
                                <div id="connectionStatus" style="text-align: center; margin-bottom: 10px; font-size: 0.85rem; font-weight: 500;">Online Mode</div>

                                <!-- Add geocoding search feature -->
                                <div class="geocoding-container">
                                    <input type="text" id="geocodingSearch" placeholder="Search for an address..." autocomplete="off">
                                    <button type="button" id="geocodingSearchBtn"><i class="fas fa-search"></i></button>
                                    <div class="geocoding-results" id="geocodingResults"></div>
                                </div>

                                <div id="leafletMap"></div>
                                <div style="margin-top:10px;">
                                    <label>Location Description:</label>
                                    <input type="text" id="modalLocationInput" style="width:100%;padding:6px; border:1px solid #ddd; border-radius:4px;" placeholder="e.g. Near Palawan Capitol gate">
                                    <p class="location-coordinates" id="coordinatesDisplay"></p>
                                </div>
                                <button id="selectLocationBtn" type="button" style="margin-top:10px;">Select</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="preview-side">
                    <div class="preview-card" style="display:block;">
                        <h4 style="color:#175868; margin-bottom:15px; text-align:center;">Preview</h4>
                        <img id="previewImg" src="images/placeholder-image.png" alt="Image Preview" style="display:block; opacity:0.5;">
                        <div style="margin-top:15px;">
                            <span class="preview-label">Type:</span>
                            <span id="previewType">-</span>
                        </div>
                        <div>
                            <span class="preview-label">Title:</span>
                            <span id="previewTitle">-</span>
                        </div>
                        <div>
                            <span class="preview-label">Date/Time:</span>
                            <span id="previewDateTime">-</span>
                        </div>
                        <div>
                            <span class="preview-label">Location:</span>
                            <span id="previewLocation">-</span>
                        </div>
                        <div>
                            <span class="preview-label">Contact:</span>
                            <span id="previewContact">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript files -->
    <script src="js/sidebar.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <!-- Defer Leaflet loading until needed -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js" defer></script>
    <script>
        // Form progress and navigation
        const formSections = document.querySelectorAll('.form-section');
        const progressSteps = document.querySelectorAll('.progress-step');
        const nextButtons = document.querySelectorAll('.next');
        const prevButtons = document.querySelectorAll('.prev');

        // Set up next button handlers
        nextButtons.forEach(button => {
            button.addEventListener('click', function() {
                const nextSection = this.dataset.next;
                const currentSection = this.closest('.form-section').dataset.section;

                // Validate current section before proceeding
                if (validateSection(currentSection)) {
                    // Hide all sections and show the next one
                    formSections.forEach(section => section.classList.remove('active'));
                    document.querySelector(`.form-section[data-section="${nextSection}"]`).classList.add('active');

                    // Update progress steps
                    progressSteps.forEach(step => {
                        if (parseInt(step.dataset.step) < nextSection) {
                            step.classList.add('completed');
                            step.classList.remove('active');
                        } else if (parseInt(step.dataset.step) == nextSection) {
                            step.classList.add('active');
                            step.classList.remove('completed');
                        } else {
                            step.classList.remove('active', 'completed');
                        }
                    });

                    // Scroll to top of form
                    window.scrollTo(0, 0);
                }
            });
        });

        // Set up previous button handlers
        prevButtons.forEach(button => {
            button.addEventListener('click', function() {
                const prevSection = this.dataset.prev;

                // Hide all sections and show the previous one
                formSections.forEach(section => section.classList.remove('active'));
                document.querySelector(`.form-section[data-section="${prevSection}"]`).classList.add('active');

                // Update progress steps
                progressSteps.forEach(step => {
                    if (parseInt(step.dataset.step) < prevSection) {
                        step.classList.add('completed');
                        step.classList.remove('active');
                    } else if (parseInt(step.dataset.step) == prevSection) {
                        step.classList.add('active');
                        step.classList.remove('completed');
                    } else {
                        step.classList.remove('active', 'completed');
                    }
                });
            });
        });

        // Validate section before proceeding to next
        function validateSection(sectionNum) {
            const section = document.querySelector(`.form-section[data-section="${sectionNum}"]`);
            let isValid = true;

            // Get all required fields in current section
            const requiredFields = section.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value) {
                    field.style.borderColor = '#ef4444';
                    isValid = false;

                    // Add shake animation
                    field.classList.add('shake');
                    setTimeout(() => field.classList.remove('shake'), 500);
                } else {
                    field.style.borderColor = '#cbd5e1';
                }
            });

            if (!isValid) {
                // Show error message
                alert('Please fill out all required fields before proceeding.');
            }

            return isValid;
        }

        // Image preview with placeholder image
        const photoInput = document.getElementById('photoInput');
        const previewImg = document.getElementById('previewImg');

        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.opacity = 1;
                }
                reader.readAsDataURL(file);
            } else {
                previewImg.src = 'images/placeholder-image.png';
                previewImg.style.opacity = 0.5;
            }
        });

        // Live preview updates for all fields
        const typeSelect = document.querySelector('select[name="type"]');
        const titleInput = document.querySelector('input[name="title"]');
        const descInput = document.querySelector('textarea[name="description"]');
        const dateInput = document.querySelector('input[name="date"]');
        const timeInput = document.querySelector('input[name="time"]');
        const locationInput = document.getElementById('locationInput');
        const contactInput = document.querySelector('input[name="contact_info"]');

        // Preview elements
        const previewType = document.getElementById('previewType');
        const previewTitle = document.getElementById('previewTitle');
        const previewDateTime = document.getElementById('previewDateTime');
        const previewLocation = document.getElementById('previewLocation');
        const previewContact = document.getElementById('previewContact');

        // Update preview on input changes
        typeSelect.addEventListener('change', () => previewType.textContent = typeSelect.value || '-');
        titleInput.addEventListener('input', () => previewTitle.textContent = titleInput.value || '-');

        // Update date/time preview when either changes
        function updateDateTimePreview() {
            if (dateInput.value && timeInput.value) {
                previewDateTime.textContent = `${dateInput.value} at ${timeInput.value}`;
            } else if (dateInput.value) {
                previewDateTime.textContent = dateInput.value;
            } else if (timeInput.value) {
                previewDateTime.textContent = timeInput.value;
            } else {
                previewDateTime.textContent = '-';
            }
        }

        dateInput.addEventListener('change', updateDateTimePreview);
        timeInput.addEventListener('change', updateDateTimePreview);

        locationInput.addEventListener('input', () => previewLocation.textContent = locationInput.value || '-');
        contactInput.addEventListener('input', () => previewContact.textContent = contactInput.value || '-');

        // Character counters
        const charCounters = document.querySelectorAll('.char-counter');

        titleInput.addEventListener('input', function() {
            charCounters[0].textContent = `${this.value.length}/${this.maxLength}`;
            if (this.value.length > this.maxLength * 0.8) {
                charCounters[0].style.color = '#e53e3e';
            } else {
                charCounters[0].style.color = '#666';
            }
        });

        descInput.addEventListener('input', function() {
            charCounters[1].textContent = `${this.value.length}/${this.maxLength}`;
            if (this.value.length > this.maxLength * 0.8) {
                charCounters[1].style.color = '#e53e3e';
            } else {
                charCounters[1].style.color = '#666';
            }
        });

        // Modal logic with Leaflet map picker
        const mapModal = document.getElementById('mapModal');
        const openMapBtn = document.getElementById('openMapBtn');
        const closeMapBtn = document.getElementById('closeMapBtn');
        const selectLocationBtn = document.getElementById('selectLocationBtn');
        const modalLocationInput = document.getElementById('modalLocationInput');
        const latitudeInput = document.getElementById('latitudeInput');
        const longitudeInput = document.getElementById('longitudeInput');
        const geocodingSearch = document.getElementById('geocodingSearch');
        const geocodingSearchBtn = document.getElementById('geocodingSearchBtn');
        const geocodingResults = document.getElementById('geocodingResults');
        const coordinatesDisplay = document.getElementById('coordinatesDisplay');

        let map, marker, mapInitialized = false;

        // Offline geocoding data - common locations in Palawan
        const offlineLocations = [{
                display_name: "Palawan State University, Puerto Princesa, Palawan, Philippines",
                lat: 9.7417,
                lon: 118.7355
            },
            {
                display_name: "Robinson's Place Palawan, Puerto Princesa, Palawan, Philippines",
                lat: 9.7438,
                lon: 118.7417
            },
            {
                display_name: "Puerto Princesa International Airport, Puerto Princesa, Palawan, Philippines",
                lat: 9.7416,
                lon: 118.7587
            },
            {
                display_name: "Palawan Capitol Building, Puerto Princesa, Palawan, Philippines",
                lat: 9.7395,
                lon: 118.7435
            },
            {
                display_name: "SM City Puerto Princesa, Puerto Princesa, Palawan, Philippines",
                lat: 9.7492,
                lon: 118.7474
            },
            {
                display_name: "Puerto Princesa City Baywalk Park, Puerto Princesa, Palawan, Philippines",
                lat: 9.7412,
                lon: 118.7241
            },
            {
                display_name: "El Nido, Palawan, Philippines",
                lat: 11.1800,
                lon: 119.4179
            },
            {
                display_name: "Coron, Palawan, Philippines",
                lat: 12.0050,
                lon: 120.2048
            },
            {
                display_name: "Underground River, Puerto Princesa, Palawan, Philippines",
                lat: 10.1688,
                lon: 118.9232
            },
            {
                display_name: "Honda Bay, Puerto Princesa, Palawan, Philippines",
                lat: 9.8624,
                lon: 118.8089
            }
        ];

        // Function to check internet connection
        function isOnline() {
            return navigator.onLine;
        }

        // Display offline status
        function showOfflineStatus(isOffline) {
            const statusContainer = document.getElementById('connectionStatus');
            if (isOffline) {
                statusContainer.textContent = 'Offline Mode - Using local data';
                statusContainer.style.color = '#ff9800';
            } else {
                statusContainer.textContent = 'Online Mode';
                statusContainer.style.color = '#22c55e';
            }
        }

        // Geocoding function with offline support
        function searchLocation(query) {
            // Clear previous results
            geocodingResults.innerHTML = '';

            // Show loading indicator
            geocodingResults.innerHTML = '<div class="geocoding-result-item">Searching...</div>';
            geocodingResults.classList.add('active');

            // Check if offline
            if (!isOnline()) {
                showOfflineStatus(true);
                performOfflineSearch(query);
                return;
            }

            showOfflineStatus(false);

            // Try online search first
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data);
                })
                .catch(error => {
                    console.error('Error with online geocoding, falling back to offline:', error);
                    showOfflineStatus(true);
                    performOfflineSearch(query);
                });
        }

        // Perform offline search using local data
        function performOfflineSearch(query) {
            // Simple search through our predefined locations
            query = query.toLowerCase();
            const results = offlineLocations.filter(location =>
                location.display_name.toLowerCase().includes(query)
            );

            displaySearchResults(results);
        }

        // Display search results in the dropdown
        function displaySearchResults(data) {
            geocodingResults.innerHTML = '';

            if (!data || data.length === 0) {
                geocodingResults.innerHTML = '<div class="geocoding-result-item">No results found</div>';
                return;
            }

            // Display results
            data.forEach(result => {
                const resultItem = document.createElement('div');
                resultItem.className = 'geocoding-result-item';
                resultItem.textContent = result.display_name;
                resultItem.addEventListener('click', () => {
                    // Set the map view to the selected location
                    const lat = parseFloat(result.lat);
                    const lng = parseFloat(result.lon);
                    map.setView([lat, lng], 16);

                    // Update marker
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng]).addTo(map);
                    }

                    // Update inputs
                    latitudeInput.value = lat;
                    longitudeInput.value = lng;
                    modalLocationInput.value = result.display_name;
                    coordinatesDisplay.textContent = `Latitude: ${lat.toFixed(5)}, Longitude: ${lng.toFixed(5)}`;

                    // Hide results
                    geocodingResults.classList.remove('active');
                    geocodingSearch.value = '';
                });
                geocodingResults.appendChild(resultItem);
            });

            geocodingResults.classList.add('active');
        }

        openMapBtn.addEventListener('click', function() {
            mapModal.style.display = 'flex';
            setTimeout(() => {
                if (!mapInitialized) {
                    // Lazy initialize the map only when needed
                    map = L.map('leafletMap').setView([9.7395, 118.7435], 16); // Default to Palawan Capitol

                    // Add offline-compatible tile layer if available
                    // For offline maps, you would need to set up a local tile server or pre-cache tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                    map.on('click', function(e) {
                        if (marker) {
                            marker.setLatLng(e.latlng);
                        } else {
                            marker = L.marker(e.latlng).addTo(map);
                        }
                        latitudeInput.value = e.latlng.lat;
                        longitudeInput.value = e.latlng.lng;
                        modalLocationInput.value = `Selected point on map`;
                        coordinatesDisplay.textContent = `Latitude: ${e.latlng.lat.toFixed(5)}, Longitude: ${e.latlng.lng.toFixed(5)}`;

                        // Try reverse geocoding if online, otherwise use a generic label
                        if (isOnline()) {
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data && data.display_name) {
                                        modalLocationInput.value = data.display_name;
                                    }
                                })
                                .catch(error => console.error('Error reverse geocoding:', error));
                        } else {
                            // Find closest offline location if within 1km
                            const closestLocation = findClosestOfflineLocation(e.latlng.lat, e.latlng.lng, 1);
                            if (closestLocation) {
                                modalLocationInput.value = `Near ${closestLocation.display_name}`;
                            }
                        }
                    });

                    mapInitialized = true;
                }
                map.invalidateSize();
                // If already selected, show marker
                if (latitudeInput.value && longitudeInput.value) {
                    const latlng = [parseFloat(latitudeInput.value), parseFloat(longitudeInput.value)];
                    map.setView(latlng, 16);
                    if (!marker) marker = L.marker(latlng).addTo(map);
                    else marker.setLatLng(latlng);
                    coordinatesDisplay.textContent = `Latitude: ${latlng[0].toFixed(5)}, Longitude: ${latlng[1].toFixed(5)}`;
                }

                // Update connection status
                showOfflineStatus(!isOnline());
            }, 200);

            // Reset geocoding search
            geocodingSearch.value = '';
            geocodingResults.classList.remove('active');

            modalLocationInput.value = locationInput.value;
            geocodingSearch.focus();
        });

        // Find closest offline location within given radius (in km)
        function findClosestOfflineLocation(lat, lon, maxDistance) {
            let closest = null;
            let closestDistance = Infinity;

            offlineLocations.forEach(location => {
                const distance = calculateDistance(lat, lon, location.lat, location.lon);
                if (distance < closestDistance && distance < maxDistance) {
                    closestDistance = distance;
                    closest = location;
                }
            });

            return closest;
        }

        // Calculate distance between two points in km using Haversine formula
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the earth in km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c; // Distance in km
            return distance;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        // Listen for online/offline events to update UI
        window.addEventListener('online', () => showOfflineStatus(false));
        window.addEventListener('offline', () => showOfflineStatus(true));

        // Geocoding search button click
        geocodingSearchBtn.addEventListener('click', function() {
            const query = geocodingSearch.value.trim();
            if (query) {
                searchLocation(query);
            }
        });

        // Geocoding search input enter key
        geocodingSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query) {
                    searchLocation(query);
                }
            }
        });

        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!geocodingResults.contains(e.target) && e.target !== geocodingSearch && e.target !== geocodingSearchBtn) {
                geocodingResults.classList.remove('active');
            }
        });

        closeMapBtn.addEventListener('click', function() {
            mapModal.style.display = 'none';
        });

        selectLocationBtn.addEventListener('click', function() {
            locationInput.value = modalLocationInput.value;
            locationInput.dispatchEvent(new Event('input'));
            mapModal.style.display = 'none';
        });

        // Also allow clicking the input to open modal
        locationInput.addEventListener('click', function() {
            openMapBtn.click();
        });

        // Drag-and-drop highlight for upload area
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#1a2d4a';
            uploadArea.style.background = '#eaf6fa';
        });
        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#4189a0';
            uploadArea.style.background = '#f7fbfd';
        });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#4189a0';
            uploadArea.style.background = '#f7fbfd';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                photoInput.files = files;
                photoInput.dispatchEvent(new Event('change'));
            }
        });

        // Mobile-friendly animations
        document.addEventListener("DOMContentLoaded", function() {
            // Add shake animation class
            const style = document.createElement('style');
            style.innerHTML = `
                @keyframes shake {
                    0% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    50% { transform: translateX(5px); }
                    75% { transform: translateX(-5px); }
                    100% { transform: translateX(0); }
                }
                .shake {
                    animation: shake 0.4s ease-in-out;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>

</html>