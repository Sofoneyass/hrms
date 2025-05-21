<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid CSRF token.";
    header("Location: my_properties.php");
    exit;
}

// Validate input
if (!isset($_POST['property_id']) || !isset($_POST['action']) || !in_array($_POST['action'], ['approve', 'reject'])) {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: my_properties.php");
    exit;
}

$property_id = $_POST['property_id'];
$action = $_POST['action'];
$owner_id = $_SESSION['user_id'];

// Verify that the property belongs to the owner
$query = "SELECT status FROM properties WHERE property_id = ? AND owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $property_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Property not found or you do not have permission to modify it.";
    header("Location: my_properties.php");
    exit;
}

$property = $result->fetch_assoc();
$stmt->close();

// Check if property is in a state that allows approval/rejection
if ($property['status'] !== 'reserved') {
    $_SESSION['error_message'] = "This property cannot be approved or rejected.";
    header("Location: my_properties.php");
    exit;
}

// Update property status
if ($action === 'approve') {
    $new_status = 'approved';
} elseif ($action === 'reject') {
    $new_status = 'available';
}

$update_query = "UPDATE properties SET status = ? WHERE property_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ss", $new_status, $property_id);

if ($update_stmt->execute()) {
    $_SESSION['success_message'] = "Property has been " . ($action === 'approve' ? "approved" : "rejected and marked available") . " successfully.";
} else {
    $_SESSION['error_message'] = "Failed to update property status.";
}

$update_stmt->close();
$conn->close();
header("Location: my_properties.php");
exit;
?>
