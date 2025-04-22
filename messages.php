<?php
require_once 'db_connection.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];  // 'tenant' or 'owner'

// Fetch messages based on user role (tenant or owner)
if ($role === 'tenant') {
    // Fetch messages sent by the tenant to the owners
    $sql = "SELECT m.*, p.title AS property_title, o.full_name AS owner_name
            FROM messages m
            JOIN properties p ON m.property_id = p.property_id
            JOIN users o ON p.owner_id = o.user_id
            WHERE m.sender_id = ? ORDER BY m.sent_at DESC";
} else if ($role === 'owner') {
    // Fetch messages received by the owner from tenants
    $sql = "SELECT m.*, p.title AS property_title, t.full_name AS tenant_name
            FROM messages m
            JOIN properties p ON m.property_id = p.property_id
            JOIN users t ON m.sender_id = t.user_id
            WHERE m.receiver_id = ? ORDER BY m.sent_at DESC";
}

$stmt = $conn->prepare($sql);

// Check for SQL preparation errors
if ($stmt === false) {
    die('SQL prepare error: ' . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .messages-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .messages-title {
            text-align: center;
            font-size: 32px;
            margin-bottom: 30px;
            color: #333;
        }

        .message-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .message-card h3 {
            margin: 0 0 10px;
            font-size: 22px;
            color: #333;
        }

        .message-card p {
            color: #666;
        }

        .message-card .message-content {
            margin-top: 10px;
            color: #444;
        }

        .message-status {
            color: #4CAF50;
            font-weight: bold;
        }

        .send-message-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #2196F3;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        .send-message-btn:hover {
            background-color: #1976D2;
        }
    </style>
</head>
<body>

<div class="messages-container">
    <h1 class="messages-title">My Messages</h1>

    <div class="message-list">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="message-card">
                    <h3>Property: <?= htmlspecialchars($row['property_title']) ?></h3>
                    <p><strong>From:</strong> <?= $role === 'tenant' ? htmlspecialchars($row['owner_name']) : htmlspecialchars($row['tenant_name']) ?></p>
                    <p><strong>Message:</strong> <?= htmlspecialchars($row['message_content']) ?></p>
                    <p class="message-status"><?= ucfirst($row['status']) ?></p>
                    <p><strong>Sent on:</strong> <?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></p>
                    
                    <!-- If the user is a tenant, show button to reply -->
                    <?php if ($role === 'tenant'): ?>
                        <a href="reply_message.php?message_id=<?= $row['message_id'] ?>" class="send-message-btn">Reply</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No messages found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
