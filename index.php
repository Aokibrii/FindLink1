<?php

session_start();

// Prevent caching of the login page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect if user is already logged in
if (isset($_SESSION['email'])) {
  // If user is admin, redirect to admin page
  if ($_SESSION['email'] === 'admin@gmail.com') {
    header("Location: admin/admin_page.php");
  } else {
    header("Location: user_page.php");
  }
  exit();
}

// Prevent direct access to login form via URL parameters
if (isset($_GET['show']) && $_GET['show'] === 'login') {
  header("Location: index.php");
  exit();
}

$errors = [
  'login' => $_SESSION['login_error'] ?? '',
  'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';
$successMessage = $_SESSION['success_message'] ?? '';

// Clean up session variables
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form'], $_SESSION['success_message']);

function showError($error)
{
  return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

function isActiveForm($formName, $activeForm)
{
  return $formName === $activeForm ? 'active' : '';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lost and Found</title>
  <link rel="icon" href="images/Icon.jpg">
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="css/style.css" />
</head>

<body>
  <div class="container">
    <div class="welcome-message text-center">
      <h1>FindLink</h1>
      <p>Lost something? Found something? Let's reconnect people with their precious belongings.</p>
      <p>Please create an account to get started or login if you already have one.</p>
      <div class="welcome-buttons">
        <button class="btn btn-primary" onclick="showForm('register-form')">Register Now</button>
        <button class="btn btn-outline-primary" onclick="showForm('login-form')">Login</button>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-12">
        <div class="form-box" id="login-form">
          <form action="login_register.php" method="post" onsubmit="return confirmLogin();">
            <h2>Login</h2>
            <?= !empty($errors['login']) ? "<p class='error-message'>{$errors['login']}</p>" : '' ?>
            <?= !empty($successMessage) ? "<p class='success-message'>{$successMessage}</p>" : '' ?>
            <input type="email" id="login-email" name="email" placeholder="Email" required />
            <div class="password-container">
              <input type="password" id="password" name="password" placeholder="Password" required />
              <img src="images/eye-close.png" alt="eye-close" id="eye-close" class="eye-icon" onclick="togglePasswordVisibility('password', 'eye-close', 'eye-open')" />
              <img src="images/eye-open.png" alt="eye-open" id="eye-open" class="eye-icon" style="display: none;" onclick="togglePasswordVisibility('password', 'eye-close', 'eye-open')" />
            </div>
            <button type="submit" name="login" id="login-btn">Login</button>
            <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Register</a></p>
          </form>
        </div>

        <div class="form-box" id="register-form">
          <form action="login_register.php" method="post" onsubmit="return confirmRegister();">
            <h2>Register</h2>
            <?= !empty($errors['register']) ? "<p class='error-message'>{$errors['register']}</p>" : '' ?>
            <input type="text" name="name" placeholder="Name" required minlength="4" maxlength="8" />
            <input type="email" id="register-email" name="email" placeholder="Email" required />
            <div class="password-container">
              <input type="password" id="register-password" name="password" placeholder="Password" required minlength="8" maxlength="15" />
              <img src="images/eye-close.png" alt="eye-close" id="register-eye-close" class="eye-icon" onclick="togglePasswordVisibility('register-password', 'register-eye-close', 'register-eye-open')" />
              <img src="images/eye-open.png" alt="eye-open" id="register-eye-open" class="eye-icon" style="display: none;" onclick="togglePasswordVisibility('register-password', 'register-eye-close', 'register-eye-open')" />
            </div>
            <div class="password-container">
              <input type="password" id="confirm-password" name="confirm" placeholder="Confirm Password" required minlength="8" maxlength="15" />
              <img src="images/eye-close.png" alt="eye-close" id="confirm-eye-close" class="eye-icon" onclick="togglePasswordVisibility('confirm-password', 'confirm-eye-close', 'confirm-eye-open')" />
              <img src="images/eye-open.png" alt="eye-open" id="confirm-eye-open" class="eye-icon" style="display: none;" onclick="togglePasswordVisibility('confirm-password', 'confirm-eye-close', 'confirm-eye-open')" />
            </div>
            <button type="submit" name="register">Register</button>
            <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="js/script.js"></script>
  <script>
    // Prevent browser back button from showing login page after logout
    window.addEventListener('pageshow', function(event) {
      if (event.persisted) {
        window.location.reload();
      }
    });

    // Show appropriate form if there are errors or success message
    <?php if (!empty($errors['login']) || !empty($errors['register']) || !empty($successMessage)): ?>
      document.addEventListener('DOMContentLoaded', function() {
        // Hide welcome message
        const welcomeMessage = document.querySelector(".welcome-message");
        if (welcomeMessage) {
          welcomeMessage.style.display = "none";
        }

        // Add centered class to container
        document.querySelector(".container").classList.add("forms-active");

        <?php if (!empty($errors['login']) || !empty($successMessage)): ?>
          // Show login form
          showForm('login-form');
        <?php else: ?>
          // Show register form
          showForm('register-form');
        <?php endif; ?>
      });
    <?php endif; ?>
  </script>
</body>

</html>