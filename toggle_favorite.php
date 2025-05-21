<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$property_id = $input['property_id'] ?? null;
$csrf_token = $input['csrf_token'] ?? null;
$tenant_id = $_SESSION['user_id'] ?? null;

if (!$tenant_id || !$property_id || !$csrf_token) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if ($csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

// Verify tenant role
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$role = $stmt->get_result()->fetch_assoc()['role'] ?? null;
$stmt->close();

if ($role !== 'tenant') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if already favorited
$stmt = $conn->prepare("SELECT favorite_id FROM favorites WHERE tenant_id = ? AND property_id = ?");
$stmt->bind_param("ss", $tenant_id, $property_id);
$stmt->execute();
$favorite = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($favorite) {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE tenant_id = ? AND property_id = ?");
    $stmt->bind_param("ss", $tenant_id, $property_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    // Add to favorites
    $stmt = $conn->prepare("INSERT INTO favorites (favorite_id, tenant_id, property_id, created_at) VALUES (UUID(), ?, ?, NOW())");
    $stmt->bind_param("ss", $tenant_id, $property_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
}
?>