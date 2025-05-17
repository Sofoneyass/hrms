<?php
session_start();
require_once 'db_connection.php';
require_once 'mailer.php';

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

    // Validate email
    if (empty($_POST['email'])) {
        throw new Exception('Email address is required');
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, email, full_name FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Database error');
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Always show success to prevent email enumeration
    if ($result->num_rows === 0) {
        $response['success'] = true;
        $response['message'] = 'If an account exists with this email, a reset link has been sent.';
        echo json_encode($response);
        exit;
    }

    $user = $result->fetch_assoc();

    // Generate unique token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store token in database
    $updateStmt = $conn->prepare("
        UPDATE users 
        SET password_reset_token = ?, 
            password_reset_expires = ? 
        WHERE user_id = ?
    ");
    $updateStmt->bind_param("sss", $token, $expires, $user['user_id']);
    $updateStmt->execute();

    // Create reset link
    $resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                 $_SERVER['HTTP_HOST'] . 
                 '/reset-password.php?token=' . $token;

    // Send email
    $subject = "Password Reset Request - JIGJIGAHOMES";
    $body = "
        <h2>Password Reset Request</h2>
        <p>Hello {$user['full_name']},</p>
        <p>We received a request to reset your password. Click the link below to proceed:</p>
        <p><a href='{$resetLink}'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you didn't request this, please ignore this email.</p>
    ";

    $mailer = new Mailer();
    if ($mailer->send($user['email'], $subject, $body)) {
        $response['success'] = true;
        $response['message'] = 'Password reset link has been sent to your email.';
    } else {
        throw new Exception('Failed to send reset email');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Password reset error: " . $e->getMessage());
} finally {
    echo json_encode($response);
    if (isset($stmt)) $stmt->close();
    if (isset($updateStmt)) $updateStmt->close();
    $conn->close();
}