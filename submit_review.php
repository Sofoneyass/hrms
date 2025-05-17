<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'tenant') {
    $tenant_id = $_SESSION['user_id'];
    $property_id = intval($_POST['property_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $stmt = $conn->prepare("INSERT INTO reviews (tenant_id, property_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $tenant_id, $property_id, $rating, $comment);
    $stmt->execute();

    header("Location: property_detail.php?property_id=$property_id");
    exit;
} else {
    echo "Unauthorized or invalid request.";
}
?>
