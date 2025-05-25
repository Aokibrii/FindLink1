<?php
session_start();
require_once 'config.php';

// Restricted access - can only be accessed locally
$clientIP = $_SERVER['REMOTE_ADDR'];
if ($clientIP !== '127.0.0.1' && $clientIP !== '::1') {
    die('Access Denied - This setup page can only be accessed locally');
}

$message = '';

// Check if admin already exists
$stmt = $connect->prepare("SELECT * FROM users WHERE email = 'admin@gmail.com'");
$stmt->execute();
$result = $stmt->get_result();
$adminExists = ($result->num_rows > 0);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    if ($adminExists) {
        $message = '<div class="alert alert-warning">Admin account already exists!</div>';
    } else {
        $name = "Admin";
        $email = "admin@gmail.com";
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if (empty($password) || strlen($password) < 8) {
            $message = '<div class="alert alert-danger">Password must be at least 8 characters long!</div>';
        } elseif ($password !== $confirmPassword) {
            $message = '<div class="alert alert-danger">Passwords do not match!</div>';
        } else {
            $role = "admin";
            $created_at = date('Y-m-d H:i:s');
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $connect->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $created_at);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Admin account created successfully!</div>';
                $adminExists = true;
            } else {
                $message = '<div class="alert alert-danger">Failed to create admin account: ' . $connect->error . '</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }

        .setup-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 30px;
            color: #343a40;
            text-align: center;
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .warning-text {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="setup-container">
            <h1>Admin Account Setup</h1>

            <?php echo $message; ?>

            <?php if ($adminExists): ?>
                <div class="alert alert-info">
                    An admin account already exists with the email: <strong>admin@gmail.com</strong>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Admin Email</label>
                        <input type="email" class="form-control" id="email" value="admin@gmail.com" disabled>
                        <div class="form-text">This email will be used for the admin account</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <p class="warning-text">Note: This page can only be accessed locally for security reasons.</p>

                    <div class="d-grid">
                        <button type="submit" name="create_admin" class="btn btn-primary">Create Admin Account</button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <a href="index.php">Return to Home Page</a>
            </div>
        </div>
    </div>

    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>