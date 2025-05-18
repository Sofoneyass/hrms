<?php
session_start();
require_once 'db_connection.php';
require_once 'auth_session.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    
    // Prevent self-deletion
    if ($userId === $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
        header("Location: manage_users.php");
        exit;
    }

    try {
        $conn->begin_transaction();

        // Delete or update related records (adjust based on your schema)
        // Example: Set user_id to NULL in activity_logs
        $stmt = $conn->prepare("UPDATE activity_logs SET user_id = NULL WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $stmt->close();

        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success_message'] = "User deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

header("Location: manage_users.php");
exit;
?>