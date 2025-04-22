<?php
require 'auth_session.php';
require 'db_connection.php';

// Get all users
$users = $conn->query("SELECT * FROM users");

// Prepare to fetch related activities for each user
$users_data = [];

while ($user = $users->fetch_assoc()) {
    $user_id = $user['user_id'];
    
    // Get bookings for each user
    $bookings = $conn->query("
        SELECT b.booking_id, b.booking_date, p.title
        FROM bookings b
        JOIN properties p ON b.property_id = p.property_id
        WHERE b.user_id = $user_id
    ");
    
    // Get messages for each user
    $messages = $conn->query("
        SELECT m.message, m.sent_at,
               (SELECT full_name FROM users WHERE user_id = m.receiver_id) AS receiver_name,
               (SELECT full_name FROM users WHERE user_id = m.sender_id) AS sender_name
        FROM messages m
        WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
    ");
    
    // Get maintenance requests for each user
    $requests = $conn->query("
        SELECT r.request_id, r.description, r.status, r.requested_at, p.title
        FROM maintenance_requests r
        JOIN properties p ON r.property_id = p.property_id
        WHERE r.tenant_id = $user_id
    ");
    
    // Get user role (as a string or as a detailed role description)
    $role = $user['role']; // Assuming `role` is stored in the `users` table as a simple string.
    
    // Get payment history for each user
    $payments = $conn->query("
        SELECT p.payment_id, p.amount, p.payment_date, p.status
        FROM payments p
        WHERE p.user_id = $user_id
    ");
    
    // Store the data for the current user
    $users_data[] = [
        'user' => $user,
        'bookings' => $bookings,
        'messages' => $messages,
        'requests' => $requests,
        'role' => $role,
        'payments' => $payments,
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Overall User Activity Report</title>
    <style>
        /* Basic styling */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f9;
    margin: 0;
    padding: 0;
}

.container {
    width: 90%;
    margin: 30px auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    border-top: 4px solid #007bff;
}

h2 {
    font-size: 32px;
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}

h3 {
    font-size: 24px;
    color: #007bff;
    margin-bottom: 10px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

.report-section {
    margin-bottom: 40px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table, th, td {
    border: 1px solid #ddd;
}

th, td {
    padding: 12px;
    text-align: left;
}

th {
    background-color: #007bff;
    color: white;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

.no-results {
    font-style: italic;
    color: #888;
}

a {
    text-decoration: none;
    color: #007bff;
}

a:hover {
    text-decoration: underline;
}

a.back-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border-radius: 4px;
    font-weight: 600;
    text-align: center;
}

a.back-btn:hover {
    background-color: #0056b3;
}

.user-report {
    margin-bottom: 40px;
    border-bottom: 2px dashed #ddd;
    padding-bottom: 20px;
}

    </style>
</head>
<body>
    <div class="container">
        <h2>Overall User Activity Report</h2>

        <?php foreach ($users_data as $data): ?>
            <div class="user-report">
                <!-- User Information -->
                <div class="report-section">
                    <h3>User Information: <?= htmlspecialchars($data['user']['full_name']) ?></h3>
                    <table>
                        <tr>
                            <th>Name</th>
                            <td><?= htmlspecialchars($data['user']['full_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= htmlspecialchars($data['user']['email']) ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?= htmlspecialchars($data['user']['phone']) ?></td>
                        </tr>
                        <tr>
                            <th>Account Created</th>
                            <td><?= $data['user']['created_at'] ?></td>
                        </tr>
                        <tr>
                            <th>Role</th>
                            <td><?= htmlspecialchars($data['role']) ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Bookings -->
                <div class="report-section">
                    <h3>Bookings</h3>
                    <?php if ($data['bookings']->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Booking ID</th>
                                <th>Booking Date</th>
                                <th>Property Title</th>
                            </tr>
                            <?php while($row = $data['bookings']->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['booking_id'] ?></td>
                                    <td><?= $row['booking_date'] ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p class="no-results">No bookings found.</p>
                    <?php endif; ?>
                </div>

                <!-- Messages -->
                <div class="report-section">
                    <h3>Messages</h3>
                    <?php if ($data['messages']->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Sender</th>
                                <th>Receiver</th>
                                <th>Message</th>
                                <th>Sent At</th>
                            </tr>
                            <?php while($row = $data['messages']->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['sender_name']) ?></td>
                                    <td><?= htmlspecialchars($row['receiver_name']) ?></td>
                                    <td><?= htmlspecialchars($row['message']) ?></td>
                                    <td><?= $row['sent_at'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p class="no-results">No messages found.</p>
                    <?php endif; ?>
                </div>

                <!-- Maintenance Requests -->
                <div class="report-section">
                    <h3>Maintenance Requests</h3>
                    <?php if ($data['requests']->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Property</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Requested At</th>
                            </tr>
                            <?php while($row = $data['requests']->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= ucfirst($row['status']) ?></td>
                                    <td><?= $row['requested_at'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p class="no-results">No maintenance requests found.</p>
                    <?php endif; ?>
                </div>

                <!-- Payment History -->
                <div class="report-section">
                    <h3>Payment History</h3>
                    <?php if ($data['payments']->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Payment ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Paid On</th>
                            </tr>
                            <?php while($row = $data['payments']->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['payment_id'] ?></td>
                                    <td>$<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= ucfirst($row['status']) ?></td>
                                    <td><?= $row['payment_date'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p class="no-results">No payment history found.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Back Button -->
        <a href="manage_users.php" class="back-btn">← Back to Manage Users</a>
    </div>
</body>
</html>
