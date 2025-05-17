<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$phone = $_POST['phone'] ?? '';
$avatar = $_FILES['avatar'] ?? null;

$response = ['success' => false];

if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $avatar_name = time() . '_' . basename($avatar['name']);
    $avatar_path = $upload_dir . $avatar_name;
    if (move_uploaded_file($avatar['tmp_name'], $avatar_path)) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET phone = ?, profile_image = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $phone, $avatar_path, $user_id);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload avatar']);
        exit;
    }
} else {
    $stmt = mysqli_prepare($conn, "UPDATE users SET phone = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $phone, $user_id);
}

if (mysqli_stmt_execute($stmt)) {
    $stmt = mysqli_prepare($conn, "SELECT full_name, email FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    $response = [
        'success' => true,
        'full_name' => $user['full_name'],
        'email' => $user['email']
    ];
} else {
    $response['error'] = 'Failed to update profile';
}

mysqli_stmt_close($stmt);
echo json_encode($response);
?>