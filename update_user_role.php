<?php
require 'auth_session.php';
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_id'], $_POST['new_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = $_POST['new_role'];

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    if ($stmt->execute()) {
        header("Location: manage_users.php?success=Role updated");
    } else {
        echo "Error updating role.";
    }
    $stmt->close();
} else {
    echo "Invalid request.";
}
?>
