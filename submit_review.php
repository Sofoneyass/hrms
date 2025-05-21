<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'tenant') {
    $tenant_id = $_SESSION['user_id']; // UUID
    $property_id = $_POST['property_id']; // UUID
    $rating = intval($_POST['rating']); // Still an integer
    $comment = trim($_POST['comment']);

    // Use "s" for UUIDs (strings), "i" for rating
    $stmt = $conn->prepare("INSERT INTO reviews (tenant_id, property_id, rating, comment) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssis", $tenant_id, $property_id, $rating, $comment);
    $stmt->execute();
    $stmt->close();

    header("Location: property_detail.php?property_id=" . urlencode($property_id));
    exit;
} else {
    echo "Unauthorized or invalid request.";
}
?>
