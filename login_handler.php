<?php
// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Secure cookie in production
session_start();

// Configuration constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

require_once 'db_connection.php';

// Function to log activity
function logActivity($conn, $user_id, $activity_type, $description, $ip_address) {
    $user_id = (is_numeric($user_id) && intval($user_id) > 0) ? intval($user_id) : null;

    if ($user_id === null) {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address, created_at) VALUES (NULL, ?, ?, ?, NOW())");
        $stmt->bind_param("sss", $activity_type, $description, $ip_address);
    } else {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $user_id, $activity_type, $description, $ip_address);
    }

    $stmt->execute();
    $stmt->close();
}


// Create default admin if none exists
$checkAdmin = $conn->query("SELECT COUNT(*) AS admin_count FROM users WHERE role = 'admin'");
$adminData = $checkAdmin->fetch_assoc();

if ($adminData['admin_count'] == 0) {
    $defaultEmail = "admin@gmail.com";
    $defaultPassword = password_hash("Admin@123", PASSWORD_DEFAULT);
    $defaultPhone = "1234567890";
    $defaultName = "Default Admin";
    $defaultRole = "admin";
    $defaultProfile = "default_admin.png";

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, profile_image, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $defaultName, $defaultEmail, $defaultPhone, $defaultPassword, $defaultRole, $defaultProfile);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $conn->insert_id, 'admin_creation', 'Default admin account created', $_SERVER['REMOTE_ADDR']);
}

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    $_SESSION['login_error'] = "Session expired. Please log in again.";
    header("Location: login.php");
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Check for login attempts
    $lockout_time = date('Y-m-d H:i:s', strtotime("-" . LOCKOUT_MINUTES . " minutes"));
    $stmt = $conn->prepare("SELECT COUNT(*) AS attempts FROM login_attempts WHERE ip = ? AND attempt_time > ?");
    $stmt->bind_param("ss", $ip_address, $lockout_time);
    $stmt->execute();
    $attempts = $stmt->get_result()->fetch_assoc()['attempts'];
    $stmt->close();

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_error'] = "Too many login attempts. Please try again after " . LOCKOUT_MINUTES . " minutes.";
        header("Location: login.php");
        exit;
    }

    // Validate input
    if (empty($login) || empty($password)) {
        $_SESSION['login_error'] = "Please provide both login and password.";
        header("Location: login.php");
        exit;
    }

    // Determine if login is email or phone
    $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);

    $query = $is_email 
        ? "SELECT user_id, email, password, role, full_name, profile_image, status FROM users WHERE email = ?"
        : "SELECT user_id, email, password, role, full_name, profile_image, status FROM users WHERE phone = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check account status
        if ($user['status'] !== 'active') {
            $_SESSION['login_error'] = "Your account is not active. Please contact support.";
            logActivity($conn, $user['user_id'], 'login_failed', 'Inactive account attempted login', $ip_address);
            header("Location: login.php");
            exit;
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_image'] = $user['profile_image'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();

            // Update last login
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();
            $stmt->close();

            // Log successful login
            logActivity($conn, $user['user_id'], 'login_success', 'User logged in successfully', $ip_address);

            // Redirect based on role
            $redirect = match($user['role']) {
                'admin' => 'admin_dashboard.php',
                'owner' => 'index.php',
                'tenant' => 'index.php',
                default => 'index.php'
            };

            header("Location: $redirect");
            exit;
        } else {
            // Log failed attempt
            $stmt = $conn->prepare("INSERT INTO login_attempts (ip, email, attempt_time) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $ip_address, $login);
            $stmt->execute();
            $stmt->close();

            logActivity($conn, $user['user_id'], 'login_failed', 'Invalid password attempted', $ip_address);
        }
    } else {
        // Log failed attempt for non-existent user
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip, email, attempt_time) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $ip_address, $login);
        $stmt->execute();
        $stmt->close();

        logActivity($conn, 0, 'login_failed', 'Attempted login with non-existent credentials', $ip_address);
    }

    $_SESSION['login_error'] = "Invalid login credentials.";
    header("Location: login.php");
    exit;
}

// Invalid access
$_SESSION['login_error'] = "Please use the login form.";
header("Location: login.php");
exit;
?>