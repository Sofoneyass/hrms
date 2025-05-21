<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit;
}

$tenant_id = $_SESSION['user_id'];

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch tenant details
$stmt = $conn->prepare("SELECT full_name, email, phone, profile_image FROM users WHERE user_id = ?");
if (!$stmt) {
    die("Error preparing tenant query: " . $conn->error);
}
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Initialize form error array
$errors = [];

// Function to send email notification
function sendPasswordChangeNotification($email, $full_name) {
    $to = $email;
    $subject = "JIGJIGAHOMES - Password Changed Successfully";
    $message = "
    <html>
    <head>
        <title>Password Change Notification</title>
    </head>
    <body style='font-family: Manrope, sans-serif; color: #333333;'>
        <h2>Password Changed</h2>
        <p>Dear " . htmlspecialchars($full_name) . ",</p>
        <p>Your JIGJIGAHOMES account password was successfully changed on " . date('F j, Y, g:i a') . ".</p>
        <p>If you did not initiate this change, please contact our support team immediately at support@jigjigahomes.com.</p>
        <p>Thank you for using JIGJIGAHOMES!</p>
        <p>Best regards,<br>JIGJIGAHOMES Team</p>
    </body>
    </html>
    ";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: JIGJIGAHOMES <no-reply@jigjigahomes.com>" . "\r\n";

    // Attempt to send email and log errors server-side
    if (!mail($to, $subject, $message, $headers)) {
        error_log("Failed to send password change notification to $email at " . date('Y-m-d H:i:s'));
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['general'] = "Invalid CSRF token.";
    } else {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $profile_image = $tenant['profile_image'];

        // Validate inputs
        if (empty($full_name)) {
            $errors['full_name'] = "Full name is required.";
        }
        if (empty($email)) {
            $errors['email'] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format.";
        } else {
            // Check for duplicate email
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("ss", $email, $tenant_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['email'] = "This email is already in use. Please try another.";
            }
            $stmt->close();
        }
        if (empty($phone)) {
            $errors['phone'] = "Phone number is required.";
        } else {
            // Check for duplicate phone
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ? AND user_id != ?");
            $stmt->bind_param("ss", $phone, $tenant_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['phone'] = "This phone number is already in use. Please try another.";
            }
            $stmt->close();
        }

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            $file_type = mime_content_type($_FILES['profile_image']['tmp_name']);
            $file_size = $_FILES['profile_image']['size'];

            if (!in_array($file_type, $allowed_types)) {
                $errors['profile_image'] = "Only JPEG, PNG, or GIF images are allowed.";
            } elseif ($file_size > $max_size) {
                $errors['profile_image'] = "Image size exceeds 2MB.";
            } else {
                $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('profile_') . '.' . $ext;
                $upload_path = 'Uploads/' . $filename;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image = $upload_path;
                    // Delete old image if it exists and is not default
                    if ($tenant['profile_image'] && file_exists($tenant['profile_image']) && strpos($tenant['profile_image'], 'default') === false) {
                        unlink($tenant['profile_image']);
                    }
                } else {
                    $errors['profile_image'] = "Failed to upload image.";
                }
            }
        }

        // Update profile if no errors
        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_image = ? WHERE user_id = ?");
            $stmt->bind_param("sssss", $full_name, $email, $phone, $profile_image, $tenant_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) { // Allow 0 if no changes
                $_SESSION['success_message'] = "Profile updated successfully.";
                $tenant = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone, 'profile_image' => $profile_image];
            } else {
                $errors['general'] = "Failed to update profile.";
            }
            $stmt->close();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['general'] = "Invalid CSRF token.";
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate inputs
        if (empty($current_password)) {
            $errors['current_password'] = "Current password is required.";
        }
        if (empty($new_password)) {
            $errors['new_password'] = "New password is required.";
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = "New password must be at least 8 characters long.";
        }
        if (empty($confirm_password)) {
            $errors['confirm_password'] = "Please confirm your new password.";
        } elseif ($new_password !== $confirm_password) {
            $errors['confirm_password'] = "New passwords do not match.";
        }

        if (empty($errors)) {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("s", $tenant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!password_verify($current_password, $user['password'])) {
                $errors['current_password'] = "Current password is incorrect.";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("ss", $hashed_password, $tenant_id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $_SESSION['success_message'] = "Password changed successfully.";
                    // Send email notification
                    sendPasswordChangeNotification($tenant['email'], $tenant['full_name']);
                } else {
                    $errors['general'] = "Failed to change password.";
                }
                $stmt->close();
            }
        }
    }
}

// Handle success message
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - JIGJIGAHOMES</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2a7f62;
            --primary-dark: #1e3c2b;
            --accent: #f0c14b;
            --text: #333333;
            --text-light: #6b7280;
            --bg: #f9fafb;
            --card-bg: rgba(255, 255, 255, 0.95);
            --glass-bg: rgba(255, 255, 255, 0.2);
            --border: #e5e7eb;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --dark-bg: #121212;
            --dark-card-bg: rgba(30, 30, 30, 0.9);
            --dark-glass-bg: rgba(50, 50, 50, 0.3);
            --dark-text: #e4e4e7;
            --dark-text-light: #a1a1aa;
            --dark-border: rgba(255, 255, 255, 0.15);
            --error: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Manrope', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            transition: background 0.3s ease, color 0.3s ease;
            overscroll-behavior: none;
        }

        body.dark-mode {
            background: var(--dark-bg);
            color: var(--dark-text);
        }

        body.dark-mode .card, body.dark-mode .message {
            background: var(--dark-card-bg);
            border: 1px solid var(--dark-border);
        }

        body.dark-mode .header, body.dark-mode .sidebar {
            background: var(--dark-glass-bg);
            border-color: var(--dark-border);
        }

        body.dark-mode .highlight {
            color: var(--accent);
        }

        body.dark-mode .sidebar a:hover,
        body.dark-mode .sidebar a.active {
            background: var(--primary);
            color: #ffffff;
        }

        /* Particle Background */
        #particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: transparent;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid var(--border);
            transition: width 0.3s ease, transform 0.3s ease;
            z-index: 1001;
        }

        .sidebar h2 {
            color: var(--primary-dark);
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        body.dark-mode .sidebar h2 {
            color: var(--dark-text);
        }

        .sidebar a {
            display: block;
            color: var(--text);
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s ease, transform 0.3s ease;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .sidebar a:hover, .sidebar a.active {
            background: var(--primary);
            color: #ffffff;
            transform: translateX(3px);
        }

        .sidebar a i {
            font-size: 1.2rem;
            color: var(--accent);
        }

        /* Header */
        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
            position: fixed;
            width: calc(100% - 250px);
            top: 0;
            left: 250px;
            z-index: 1000;
            transition: background 0.3s ease;
        }

        .header h1 {
            font-size: 24px;
            color: var(--primary-dark);
        }

        body.dark-mode .header h1 {
            color: var(--dark-text);
        }

        .user-info {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            transition: transform 0.3s ease;
        }

        .user-info img:hover {
            transform: scale(1.1);
        }

        .user-info span {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        body.dark-mode .user-info span {
            color: var(--accent);
        }

        .profile-dropdown {
            position: absolute;
            top: 50px;
            right: 1rem;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 180px;
            display: none;
            z-index: 1002;
        }

        body.dark-mode .profile-dropdown {
            background: var(--dark-glass-bg);
            border-color: var(--dark-border);
        }

        .profile-dropdown.active {
            display: block;
        }

        .profile-dropdown a, .profile-dropdown button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.8rem;
            color: var(--text);
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 0.9rem;
        }

        body.dark-mode .profile-dropdown a, body.dark-mode .profile-dropdown button {
            color: var(--dark-text);
        }

        .profile-dropdown a:hover, .profile-dropdown button:hover {
            background: var(--primary);
            color: #ffffff;
        }

        .mode-indicator i {
            font-size: 1rem;
            color: var(--accent);
        }

        .sidebar-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            background: var(--primary-dark);
            border: 1px solid var(--border);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--accent);
            transition: transform 0.3s ease;
            z-index: 1002;
        }

        body.dark-mode .sidebar-toggle {
            border-color: var(--dark-border);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 80px 20px 20px;
            transition: margin-left 0.3s ease;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
        }

        .success {
            background: rgba(76, 175, 80, 0.3);
            color: #28a745;
        }

        .error {
            background: rgba(244, 67, 54, 0.3);
            color: #dc3545;
        }

        .settings-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            max-width: 600px;
            margin: 0 auto;
        }

        .settings-section {
            margin-bottom: 30px;
        }

        .settings-section h3 {
            color: var(--primary-dark);
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        body.dark-mode .settings-section h3 {
            color: var(--dark-text);
        }

        .form-group {
            margin-bottom: 15px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            color: var(--text);
            margin-bottom: 5px;
        }

        body.dark-mode .form-group label {
            color: var(--dark-text);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="password"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 0.95rem;
            background: var(--card-bg);
            color: var(--text);
        }

        body.dark-mode .form-group input {
            background: var(--dark-card-bg);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(42, 127, 98, 0.3);
        }

        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .error-message.active {
            display: block;
        }

        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #ffffff;
            font-size: 0.95rem;
            background: var(--primary);
            transition: background 0.3s ease;
        }

        .action-btn:hover {
            background: var(--primary-dark);
        }

        .preferences {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .preference-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preference-item label {
            font-size: 0.95rem;
            color: var(--text);
        }

        body.dark-mode .preference-item label {
            color: var(--dark-text);
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-250px);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                width: 100%;
                left: 0;
            }

            .sidebar-toggle {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .settings-container {
                padding: 15px;
            }
        }

        @media (max-width: 600px) {
            .header h1 {
                font-size: 1.2rem;
            }

            .user-info img {
                width: 32px;
                height: 32px;
            }

            .user-info span {
                font-size: 0.85rem;
            }

            .profile-dropdown {
                width: 160px;
                top: 45px;
                right: 0.5rem;
            }

            .settings-section h3 {
                font-size: 1.1rem;
            }

            .action-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Particle Background -->
    <div id="particles"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-toggle" id="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <h2>JIGJIGAHOMES</h2>
        <a href="tenant_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="browse_properties.php"><i class="fas fa-search"></i> Browse Properties</a>
        <a href="my_favorites.php"><i class="fas fa-heart"></i> My Favorites</a>
        <a href="my_leases.php"><i class="fas fa-file-signature"></i> My Leases</a>
        <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Header -->
    <div class="header" id="header">
        <h1>Settings</h1>
        <div class="user-info">
            <img src="<?= htmlspecialchars($tenant['profile_image'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($tenant['full_name']) . '&background=2a7f62&color=fff') ?>" alt="Profile">
            <span><?= htmlspecialchars($tenant['full_name']) ?></span>
            <div class="profile-dropdown" id="profile-dropdown">
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
                <button id="dark-mode-toggle"><i class="fas fa-moon mode-indicator"></i> <span>Dark Mode</span></button>
                <form method="POST" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <?php if ($success_message): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['general'])): ?>
            <div class="message error"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>
        <div class="settings-container">
            <!-- Profile Update -->
            <div class="settings-section">
                <h3>Update Profile</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($tenant['full_name']) ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="error-message active"><?= htmlspecialchars($errors['full_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($tenant['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message active"><?= htmlspecialchars($errors['email']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($tenant['phone']) ?>" required>
                        <?php if (isset($errors['phone'])): ?>
                            <span class="error-message active"><?= htmlspecialchars($errors['phone']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                        <?php if (isset($errors['profile_image'])): ?>
                            <span class="error-message active"><?= htmlspecialchars($errors['profile_image']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="action-btn">Update Profile</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="settings-section">
                <h3>Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <?php if (isset($errors['current_password'])): ?>
                            <span class="error-message active"><?= htmlspecialchars($errors['current_password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <?php if (isset($errors['new_password'])): ?>
                            <span class="error-message active"><?= htmlspecialchars($errors['new_password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="error-message active"><?= htmlspecialchars($errors['confirm_password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="action-btn">Change Password</button>
                </form>
            </div>

            <!-- Preferences -->
            <div class="settings-section">
                <h3>Preferences</h3>
                <div class="preferences">
                    <div class="preference-item">
                        <input type="checkbox" id="dark_mode" checked>
                        <label for="dark_mode">Enable Dark Mode</label>
                    </div>
                    <div class="preference-item">
                        <input type="checkbox" id="notifications">
                        <label for="notifications">Receive Email Notifications</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        // Particle Background
        particlesJS('particles', {
            particles: {
                number: { value: 60, density: { enable: true, value_area: 800 } },
                color: { value: ['#2a7f62', '#f0c14b', '#1e3c2b'] },
                shape: { type: 'circle' },
                opacity: { value: 0.4, random: true },
                size: { value: 2, random: true },
                line_linked: { enable: false },
                move: { enable: true, speed: 0.8, direction: 'none', random: true }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' } },
                modes: { repulse: { distance: 80, duration: 0.4 }, push: { particles_nb: 3 } }
            },
            retina_detect: true
        });

        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const mainContent = document.getElementById('main-content');
        const header = document.getElementById('header');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
            header.classList.toggle('sidebar-active');
            sidebarToggle.innerHTML = sidebar.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Profile Dropdown
        const userInfo = document.querySelector('.user-info');
        const profileDropdown = document.getElementById('profile-dropdown');
        userInfo.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!userInfo.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const darkModeCheckbox = document.getElementById('dark_mode');
        const body = document.body;
        const modeIndicator = darkModeToggle.querySelector('.mode-indicator');
        const modeText = darkModeToggle.querySelector('span');
        const isDarkMode = localStorage.getItem('dark-mode') === 'enabled';

        if (isDarkMode) {
            body.classList.add('dark-mode');
            modeIndicator.className = 'fas fa-sun mode-indicator';
            modeText.textContent = 'Light Mode';
            darkModeCheckbox.checked = true;
        }

        darkModeToggle.addEventListener('click', () => {
            toggleDarkMode();
        });

        darkModeCheckbox.addEventListener('change', () => {
            toggleDarkMode();
        });

        function toggleDarkMode() {
            body.classList.toggle('dark-mode');
            const isNowDark = body.classList.contains('dark-mode');
            localStorage.setItem('dark-mode', isNowDark ? 'enabled' : 'disabled');
            modeIndicator.className = isNowDark ? 'fas fa-sun mode-indicator' : 'fas fa-moon mode-indicator';
            modeText.textContent = isNowDark ? 'Light Mode' : 'Dark Mode';
            darkModeCheckbox.checked = isNowDark;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>