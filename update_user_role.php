<?php
session_start();
require_once 'db_connection.php';  // Database connection file

// Ensure that the user is an admin
if ($_SESSION['role'] !== 'admin') {
    echo "Access denied.";
    exit();
}

// Process the form submission
if (isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = $_POST['role'];

    // Validate new role value
    $valid_roles = ['admin', 'owner', 'tenant'];
    if (!in_array($new_role, $valid_roles)) {
        echo "Invalid role selected.";
        exit();
    }

    // Prepare SQL query to update role
    $sql = "UPDATE users SET role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $new_role, $user_id);

    if ($stmt->execute()) {
        echo "User role updated successfully!";
    } else {
        echo "Error updating role.";
    }

    $stmt->close();
}
?>
