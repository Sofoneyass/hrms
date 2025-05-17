<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An error occurred. Please try again.'
];

try {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid security token');
    }

    // Validate required fields
    $required = ['token', 'user_id', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception('All fields are required');
        }
    }

    if ($_POST['password'] !== $_POST['confirm_password']) {
        throw new Exception('Passwords do not match');
    }

    if (strlen($_POST['password']) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    // Verify token and user
    $stmt = $conn->prepare("
        SELECT user_id 
        FROM users 
        WHERE user_id = ? 
        AND password_reset_token = ? 
        AND password_reset_expires > NOW()
    ");
    $stmt->bind_param("ss", $_POST['user_id'], $_POST['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid or expired reset token');
    }

    // Update password
    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("
        UPDATE users 
        SET password = ?,
            password_reset_token = NULL,
            password_reset_expires = NULL
        WHERE user_id = ?
    ");
    $stmt->bind_param("ss", $password_hash, $_POST['user_id']);
    $stmt->execute();

    // Log activity
    $log_id = bin2hex(random_bytes(16));
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $activity_stmt = $conn->prepare("
        INSERT INTO activity_logs (
            log_id, user_id, activity_type, description, ip_address, created_at
        ) VALUES (?, ?, 'password_reset', 'Password reset successful', ?, NOW())
    ");
    $activity_stmt->bind_param("sss", $log_id, $_POST['user_id'], $ip_address);
    $activity_stmt->execute();

    $response['success'] = true;
    $response['message'] = 'Password updated successfully. Redirecting to login...';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Password reset error: " . $e->getMessage());
} finally {
    echo json_encode($response);
    if (isset($stmt)) $stmt->close();
    if (isset($activity_stmt)) $activity_stmt->close();
    $conn->close();
}