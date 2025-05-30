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

// Get current user email
$user_email = $_SESSION['email'];

// Handle mark as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $message_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_email = ?");
    $stmt->bind_param("is", $message_id, $user_email);
    $stmt->execute();
    header("Location: messages.php");
    exit();
}

// Handle delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND receiver_email = ?");
    $stmt->bind_param("is", $message_id, $user_email);
    $stmt->execute();
    header("Location: messages.php");
    exit();
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver = $_POST['receiver_email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user_email, $receiver, $subject, $message);

    if ($stmt->execute()) {
        $success_message = "Message sent successfully!";
    } else {
        $error_message = "Error sending message: " . $conn->error;
    }
}

// Fetch messages for the current user
$sql = "SELECT * FROM messages WHERE receiver_email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$messages = $stmt->get_result();

// Count unread messages
$unread_query = "SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_email = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("s", $user_email);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Findlink</title>
    <link rel="icon" href="images/Icon.jpg">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/user_page.css">
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

        /* Messages specific styles */
        .messages-container {
            background-color: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .new-message-btn {
            background: linear-gradient(135deg, #4e73f8, #3b5de7);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(59, 93, 231, 0.2);
            transition: all 0.3s ease;
        }

        .new-message-btn:hover {
            background: linear-gradient(135deg, #3b5de7, #2a4bdb);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(59, 93, 231, 0.3);
        }

        .message-list {
            max-height: 65vh;
            overflow-y: auto;
        }

        .message-item {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
            border-left: 4px solid #ddd;
        }

        .message-item.unread {
            background-color: #f0f7ff;
            border-left: 4px solid #4e73f8;
        }

        .message-icon {
            background-color: #e6e6e6;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            color: #666;
        }

        .message-item.unread .message-icon {
            background-color: #4e73f8;
            color: white;
        }

        .message-content {
            flex-grow: 1;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .message-sender {
            font-weight: 600;
            color: #333;
        }

        .message-time {
            color: #999;
            font-size: 0.85rem;
        }

        .message-subject {
            font-weight: 500;
            margin-bottom: 5px;
            color: #444;
        }

        .message-body {
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .message-actions {
            display: flex;
            gap: 10px;
        }

        .message-action-btn {
            background-color: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            transition: color 0.2s ease;
            padding: 5px;
        }

        .message-action-btn:hover {
            color: #4e73f8;
        }

        .message-action-btn.delete:hover {
            color: #ef4444;
        }

        .no-messages {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }

        .no-messages i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* Modal styles */
        .message-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }

        .message-modal-content {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .message-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .message-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            transition: color 0.2s ease;
        }

        .close-modal:hover {
            color: #333;
        }

        .message-form input,
        .message-form textarea {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .message-form input:focus,
        .message-form textarea:focus {
            outline: none;
            border-color: #4e73f8;
        }

        .message-form textarea {
            min-height: 150px;
            resize: vertical;
        }

        .message-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .message-form-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cancel-btn {
            background-color: #f3f4f6;
            color: #666;
            border: none;
        }

        .cancel-btn:hover {
            background-color: #e5e7eb;
        }

        .send-btn {
            background-color: #4e73f8;
            color: white;
            border: none;
        }

        .send-btn:hover {
            background-color: #3b5de7;
        }

        /* Alert styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="header">
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
                    <a href="messages.php" class="side-nav-item active">
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

        <main id="mainContent">
            <div class="messages-container">
                <div class="messages-header">
                    <h1>Messages</h1>
                    <button class="new-message-btn" onclick="openNewMessageModal()">
                        <i class="fa-solid fa-plus"></i> New Message
                    </button>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>

                <div class="message-list">
                    <?php if ($messages->num_rows > 0): ?>
                        <?php while ($message = $messages->fetch_assoc()):
                            $isRead = $message['is_read'] ? '' : 'unread';
                            $timestamp = strtotime($message['created_at']);
                            $formattedDate = date('M d, Y, g:i a', $timestamp);
                            $messagePreview = substr($message['message'], 0, 100) . (strlen($message['message']) > 100 ? '...' : '');
                        ?>
                            <div class="message-item <?= $isRead ?>">
                                <div class="message-icon">
                                    <i class="fa-solid fa-envelope<?= $isRead ? '-open' : '' ?>"></i>
                                </div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <span class="message-sender"><?= htmlspecialchars($message['sender_email']) ?></span>
                                        <span class="message-time"><?= $formattedDate ?></span>
                                    </div>
                                    <div class="message-subject"><?= htmlspecialchars($message['subject']) ?></div>
                                    <div class="message-body"><?= htmlspecialchars($messagePreview) ?></div>
                                    <div class="message-actions">
                                        <?php if (!$message['is_read']): ?>
                                            <a href="?mark_read=<?= $message['id'] ?>" class="message-action-btn">
                                                <i class="fa-solid fa-check"></i> Mark as Read
                                            </a>
                                        <?php endif; ?>
                                        <button class="message-action-btn" onclick="viewMessage('<?= htmlspecialchars($message['sender_email']) ?>', '<?= htmlspecialchars($message['subject']) ?>', '<?= htmlspecialchars(str_replace(array("\r", "\n"), array('\\r', '\\n'), $message['message'])) ?>')">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>
                                        <a href="?delete=<?= $message['id'] ?>" class="message-action-btn delete" onclick="return confirm('Are you sure you want to delete this message?')">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-messages">
                            <i class="fa-solid fa-inbox"></i>
                            <p>You have no messages yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- New Message Modal -->
            <div class="message-modal" id="newMessageModal">
                <div class="message-modal-content">
                    <div class="message-modal-header">
                        <h2 class="message-modal-title">New Message</h2>
                        <button class="close-modal" onclick="closeNewMessageModal()">&times;</button>
                    </div>
                    <form class="message-form" method="post" action="messages.php">
                        <div>
                            <label for="receiver_email">To:</label>
                            <input type="email" id="receiver_email" name="receiver_email" placeholder="Recipient's email" required>
                        </div>
                        <div>
                            <label for="subject">Subject:</label>
                            <input type="text" id="subject" name="subject" placeholder="Subject" required>
                        </div>
                        <div>
                            <label for="message">Message:</label>
                            <textarea id="message" name="message" placeholder="Write your message here..." required></textarea>
                        </div>
                        <div class="message-form-actions">
                            <button type="button" class="message-form-btn cancel-btn" onclick="closeNewMessageModal()">Cancel</button>
                            <button type="submit" name="send_message" class="message-form-btn send-btn">Send</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Message Modal -->
            <div class="message-modal" id="viewMessageModal">
                <div class="message-modal-content">
                    <div class="message-modal-header">
                        <h2 class="message-modal-title">Message</h2>
                        <button class="close-modal" onclick="closeViewMessageModal()">&times;</button>
                    </div>
                    <div class="message-details">
                        <p><strong>From:</strong> <span id="viewMessageSender"></span></p>
                        <p><strong>Subject:</strong> <span id="viewMessageSubject"></span></p>
                        <div class="message-body-full">
                            <p><strong>Message:</strong></p>
                            <div id="viewMessageBody" style="white-space: pre-wrap; background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 10px;"></div>
                        </div>
                        <div class="message-form-actions" style="margin-top: 20px;">
                            <button type="button" class="message-form-btn cancel-btn" onclick="closeViewMessageModal()">Close</button>
                            <button type="button" class="message-form-btn send-btn" onclick="replyToMessage()">Reply</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar.js"></script>
    <script>
        // New Message Modal
        function openNewMessageModal() {
            document.getElementById('newMessageModal').style.display = 'flex';
        }

        function closeNewMessageModal() {
            document.getElementById('newMessageModal').style.display = 'none';
        }

        // View Message Modal
        function viewMessage(sender, subject, message) {
            // Replace escaped line breaks with actual line breaks
            message = message.replace(/\\r\\n|\\n|\\r/g, '\n');

            document.getElementById('viewMessageSender').textContent = sender;
            document.getElementById('viewMessageSubject').textContent = subject;
            document.getElementById('viewMessageBody').textContent = message;
            document.getElementById('viewMessageModal').style.display = 'flex';
        }

        function closeViewMessageModal() {
            document.getElementById('viewMessageModal').style.display = 'none';
        }

        function replyToMessage() {
            const sender = document.getElementById('viewMessageSender').textContent;
            const subject = document.getElementById('viewMessageSubject').textContent;

            // Close view modal
            closeViewMessageModal();

            // Open new message modal with pre-filled data
            document.getElementById('receiver_email').value = sender;
            document.getElementById('subject').value = 'Re: ' + subject;
            openNewMessageModal();

            // Focus on message body
            setTimeout(() => {
                document.getElementById('message').focus();
            }, 100);
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const newMessageModal = document.getElementById('newMessageModal');
            const viewMessageModal = document.getElementById('viewMessageModal');

            if (event.target === newMessageModal) {
                closeNewMessageModal();
            }

            if (event.target === viewMessageModal) {
                closeViewMessageModal();
            }
        };
    </script>
</body>

</html>