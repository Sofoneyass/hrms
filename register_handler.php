<?php
session_start();
require_once 'db_connection.php';

// Configuration constants
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Set JSON headers
header('Content-Type: application/json');

// Function to log activity
function logActivity($conn, $user_id, $activity_type, $description, $ip_address) {
    if ($user_id) {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $user_id, $activity_type, $description, $ip_address);
    } else {
        $stmt = $conn->prepare("INSERT INTO activity_logs (activity_type, description, ip_address, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $activity_type, $description, $ip_address);
    }
    $stmt->execute();
    $stmt->close();
}

// Function to send notification to admins
function notifyAdmins($conn, $message) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'admin' AND status = 'active' AND deleted_at IS NULL");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($admin = $result->fetch_assoc()) {
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
        $stmt_notify->bind_param("ss", $admin['user_id'], $message);
        $stmt_notify->execute();
        $stmt_notify->close();
    }
    $stmt->close();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logActivity($conn, null, 'register_failed', 'Invalid CSRF token', $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid form submission']);
        exit;
    }
    
   unset($_SESSION['csrf_token']); // prevent reuse

    // Validate inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';

    // Input validation
    if (empty($full_name) || empty($email) || empty($password) || empty($phone) || !in_array($role, ['owner', 'tenant'])) {
        logActivity($conn, null, 'register_failed', 'Invalid input data', $_SERVER['REMOTE_ADDR']);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields with valid values']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logActivity($conn, null, 'register_failed', 'Invalid email format', $_SERVER['REMOTE_ADDR']);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    if (strlen($password) < 8) {
        logActivity($conn, null, 'register_failed', 'Password too short', $_SERVER['REMOTE_ADDR']);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }

    if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        logActivity($conn, null, 'register_failed', 'Invalid phone number', $_SERVER['REMOTE_ADDR']);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        logActivity($conn, null, 'register_failed', 'Duplicate email', $_SERVER['REMOTE_ADDR']);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email is already registered']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Hash password
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $created_at = date("Y-m-d H:i:s");
    $profile_image = null;

    // Handle image upload
    if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['size'] > 0) {
        $target_dir = "Uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $filename = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $filename;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file
        if (!in_array($file_type, ALLOWED_TYPES)) {
            logActivity($conn, null, 'register_failed', 'Invalid image type', $_SERVER['REMOTE_ADDR']);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed']);
            exit;
        }

        if ($_FILES['profile_image']['size'] > MAX_FILE_SIZE) {
            logActivity($conn, null, 'register_failed', 'Image file too large', $_SERVER['REMOTE_ADDR']);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image file is too large. Maximum size is 5MB']);
            exit;
        }

        if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            logActivity($conn, null, 'register_failed', 'Image upload failed', $_SERVER['REMOTE_ADDR']);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error uploading image']);
            exit;
        }

        $profile_image = $target_file;
    }

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, role, profile_image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param("sssssss", $full_name, $email, $password_hashed, $phone, $role, $profile_image, $created_at);

    if ($stmt->execute()) {
        // Retrieve the generated user_id
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND created_at = ?");
        $stmt->bind_param("ss", $email, $created_at);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_id = $result->num_rows > 0 ? $result->fetch_assoc()['user_id'] : null;
        $stmt->close();

        if ($user_id) {
            // Log activity
            logActivity($conn, $user_id, 'register_success', 'User registered successfully', $_SERVER['REMOTE_ADDR']);

            // Notify admins
            $notification_message = "New user registered: {$full_name} ({$email}) with role {$role}";
            notifyAdmins($conn, $notification_message);

            // Clear CSRF token
            unset($_SESSION['csrf_token']);

            echo json_encode(['success' => true, 'message' => 'Registration successful! Redirecting to login...', 'redirect' => 'login.php']);
            exit;
        } else {
            logActivity($conn, null, 'register_failed', 'Could not retrieve user_id', $_SERVER['REMOTE_ADDR']);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to retrieve user ID after registration']);
            exit;
        }
    } else {
        logActivity($conn, null, 'register_failed', 'Database error: ' . $stmt->error, $_SERVER['REMOTE_ADDR']);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
?>