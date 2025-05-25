<?php
session_start();
// Optional: Only allow logged-in users (user or admin)
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$host = 'localhost';
$db   = 'user_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$user_email = $_SESSION['email'];
$is_admin = ($user_email === 'admin@gmail.com');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if ($is_admin) {
        // Admin sees all posts
        $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
    } else {
        // User sees only their posts
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_email = ? ORDER BY created_at DESC");
        $stmt->execute([$user_email]);
    }
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Lost and Found Items</title>
    <link rel="icon" href="images/Icon.jpg">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <style>
        .table-img {
            max-width: 80px;
            max-height: 80px;
            border-radius: 6px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <h2 class="mb-4">All Lost and Found Items</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Location</th>
                        <th>Date</th>
                        <?php
                        // Check if type column exists
                        $typeColumnExists = false;
                        try {
                            $typeColumnExists = $pdo->query("SHOW COLUMNS FROM posts LIKE 'type'")->rowCount() > 0;
                        } catch (Exception $e) {
                        }
                        if ($typeColumnExists) {
                            echo '<th>Type</th>';
                        }
                        ?>
                        <th>Time</th>
                        <th>Contact Info</th>
                        <th>Photo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($posts) > 0): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['id']) ?></td>
                                <td><?= htmlspecialchars($post['title']) ?></td>
                                <td><?= htmlspecialchars($post['description']) ?></td>
                                <td><?= htmlspecialchars($post['location']) ?></td>
                                <td><?= htmlspecialchars($post['date']) ?></td>
                                <?php if ($typeColumnExists): ?>
                                    <td>
                                        <?php
                                        if (isset($post['type'])) {
                                            $type = strtolower($post['type']);
                                            if ($type === 'lost') {
                                                echo '<span style="color:#2196f3;"><i class="fas fa-question-circle me-1"></i>Lost</span>';
                                            } elseif ($type === 'found') {
                                                echo '<span style="color:#43a047;"><i class="fas fa-check-circle me-1"></i>Found</span>';
                                            } else {
                                                echo ucfirst($type);
                                            }
                                        } else {
                                            echo '<span class="text-muted">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($post['time']) ?></td>
                                <td><?= htmlspecialchars($post['contact_info']) ?></td>
                                <td>
                                    <?php
                                    $img = !empty($post['photo_path']) && file_exists($post['photo_path']) ? $post['photo_path'] : 'images/default-item.jpg';
                                    ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="Photo" class="table-img">
                                </td>
                                <td>
                                    <?php
                                    if (isset($post['status'])) {
                                        $status = $post['status'];
                                        $badge = $status === 'claimed' ? 'success' : 'warning';
                                        echo "<span class='badge bg-$badge'>" . ucfirst($status) . "</span>";
                                    } else {
                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">No items found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="admin/dashboard.php" class="btn btn-secondary mt-3">Back to Admin Panel</a>
    </div>
</body>

</html>