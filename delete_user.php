<?php
include 'db_connection.php';

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    header("Location: admin_dashboard.php");
    exit;
}
