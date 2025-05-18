<?php
require_once 'db_connection.php';

if (!isset($_POST['property_id'])) {
    $_SESSION['error_message'] = "Property ID not provided.";
    header("Location: manage_properties.php");
    exit;
}

$propertyId = $_POST['property_id'];
try {
    // Fetch image to delete
    $stmt = $conn->prepare("SELECT image FROM properties WHERE property_id = ?");
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

    // Delete property
    $stmt = $conn->prepare("DELETE FROM properties WHERE property_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $stmt->close();

    // Delete image if exists
    if ($property['image'] && file_exists("Uploads/" . $property['image'])) {
        unlink("Uploads/" . $property['image']);
    }

    $_SESSION['success_message'] = "Property deleted successfully.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error deleting property: " . $e->getMessage();
}

$conn->close();
header("Location: manage_properties.php");
exit;
?>