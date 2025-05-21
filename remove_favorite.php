<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => '❌ Invalid CSRF token.'];
    header("Location: my_favorites.php");
    exit;
}

if (!isset($_POST['property_id'])) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => '❌ Invalid request.'];
    header("Location: my_favorites.php");
    exit;
}

$property_id = $_POST['property_id'];
$tenant_id = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM favorites WHERE tenant_id = ? AND property_id = ?");
$stmt->bind_param("ss", $tenant_id, $property_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['flash'] = ['type' => 'success', 'message' => '✅ Property removed from favorites successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => '❌ Failed to remove property from favorites.'];
}

$stmt->close();
$conn->close();
header("Location: my_favorites.php");
exit;
?>
