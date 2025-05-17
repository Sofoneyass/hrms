<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connection.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tenant') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a tenant to favorite properties.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$property_id = $data['property_id'] ?? null;
$csrf_token = $data['csrf_token'] ?? null;

// Validate CSRF token
if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

// Validate property ID
if (!is_numeric($property_id) || $property_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID.']);
    exit;
}

// Check if property exists
$stmt = $conn->prepare("SELECT id FROM properties WHERE id = ?");
$stmt->bind_param("i", $property_id);
if (!$stmt->execute()) {
    error_log("Error checking property existence: " . $stmt->error);
    echo json_encode(['success' => false, 'message' 'Database error while checking property.']);
    $stmt->close();
    $conn->close();
    exit;
}
if (!$stmt->get_result()->num_rows) {
    echo json_encode(['success' => false, 'message' => 'Property not found.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Check if already favorited
$stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
$stmt->bind_param("ii", $user_id, $property_id);
if (!$stmt->execute()) {
    error_log("Error checking favorite status: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error while checking favorite status.']);
    $stmt->close();
    $conn->close();
    exit;
}
$result = $stmt->get_result();
$is_favorited = $result->num_rows > 0;
$stmt->close();

if ($is_favorited) {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND property_id = ?");
    $stmt->bind_param("ii", $user_id, $property_id);
    if (!$stmt->execute()) {
        error_log("Error removing favorite: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to remove property from favorites.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Property removed from favorites.']);
} else {
    // Add to favorites
    $stmt = $conn->prepare("INSERT INTO favorites (user_id, property_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $property_id);
    if (!$stmt->execute()) {
        error_log("Error adding favorite: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to add property to favorites.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Property added to favorites.']);
}

$conn->close();
?>