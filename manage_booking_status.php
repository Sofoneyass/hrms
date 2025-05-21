<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid CSRF token.";
    header("Location: my_favorites.php");
    exit;
}

// Validate input
if (!isset($_POST['booking_id']) || !isset($_POST['property_id']) || !isset($_POST['action']) || !in_array($_POST['action'], ['approve', 'reject'])) {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: my_favorites.php");
    exit;
}

$booking_id = $_POST['booking_id'];
$property_id = $_POST['property_id'];
$action = $_POST['action'];
$tenant_id = $_SESSION['user_id'];

// Verify booking belongs to the tenant
$stmt = $conn->prepare("SELECT status FROM bookings WHERE booking_id = ? AND property_id = ? AND tenant_id = ? AND status = 'pending'");
$stmt->bind_param("sss", $booking_id, $property_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Booking not found or not in a pending state.";
    header("Location: my_favorites.php");
    exit;
}
$stmt->close();

// Update booking status
$new_booking_status = ($action === 'approve') ? 'confirmed' : 'rejected';
$update_query = "UPDATE bookings SET status = ? WHERE booking_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ss", $new_booking_status, $booking_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Update property status
    $new_property_status = ($action === 'approve') ? 'reserved' : 'available';
    $stmt = $conn->prepare("UPDATE properties SET status = ? WHERE property_id = ?");
    $stmt->bind_param("ss", $new_property_status, $property_id);
    $stmt->execute();

    $_SESSION['success_message'] = "Booking " . ($action === 'approve' ? "confirmed" : "rejected") . " successfully.";
} else {
    $_SESSION['error_message'] = "Failed to update booking status.";
}

$stmt->close();
$conn->close();
header("Location: my_favorites.php");
exit;
?>