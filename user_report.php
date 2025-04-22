<?php
require 'auth_session.php';
require 'db_connection.php';

$query = "
    SELECT 
        u.user_id, u.full_name, u.email, u.role, u.created_at,
        COUNT(DISTINCT b.booking_id) AS total_bookings,
        COUNT(DISTINCT m.message_id) AS total_messages,
        COUNT(DISTINCT r.request_id) AS total_requests
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.user_id
    LEFT JOIN messages m ON u.user_id = m.sender_id
    LEFT JOIN maintenance_requests r ON u.user_id = r.tenant_id
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Reports</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>User Reports Overview</h2>
    <table>
        <thead>
            <tr>
                <th>User ID</th><th>Name</th><th>Role</th><th>Bookings</th><th>Messages</th><th>Requests</th><th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['user_id'] ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= $row['role'] ?></td>
                <td><?= $row['total_bookings'] ?></td>
                <td><?= $row['total_messages'] ?></td>
                <td><?= $row['total_requests'] ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
