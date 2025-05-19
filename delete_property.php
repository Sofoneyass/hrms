<?php
session_start();
require_once 'db_connection.php';

if (!isset($_POST['property_id'])) {
    $_SESSION['error_message'] = "Property ID not provided.";
    header("Location: manage_properties.php");
    exit;
}

$propertyId = $_POST['property_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if property exists
    $stmt = $conn->prepare("SELECT property_id FROM properties WHERE property_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$property) {
        throw new Exception("Property not found.");
    }

    // Fetch and delete associated photos
    $stmt = $conn->prepare("SELECT photo_url FROM property_photos WHERE property_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Delete photos from filesystem
    foreach ($photos as $photo) {
        $filePath = "Uploads/" . basename($photo['photo_url']);
        if ($photo['photo_url'] && file_exists($filePath)) {
            if (!unlink($filePath)) {
                throw new Exception("Failed to delete photo: " . $filePath);
            }
        }
    }

    // Delete related records that don't cascade
    // For tables without ON DELETE CASCADE (e.g., leases)
    $stmt = $conn->prepare("DELETE FROM leases WHERE property_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $stmt->close();

    // Other tables (bookings, amenities, favorites, messages, maintenance_requests, property_photos, reviews)
    // have ON DELETE CASCADE, so they will be handled automatically by MySQL

    // Delete the property
    $stmt = $conn->prepare("DELETE FROM properties WHERE property_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    $_SESSION['success_message'] = "Property and associated records deleted successfully.";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error deleting property: " . $e->getMessage();
}

$conn->close();
header("Location: manage_properties.php");
exit;
?>