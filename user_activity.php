<?php
require 'auth_session.php';
require 'db_connection.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    die("Invalid or missing User ID.");
}

// Get basic user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

// Get bookings
$bookings = $conn->query("
    SELECT b.booking_id, b.booking_date, p.title 
    FROM bookings b
    JOIN properties p ON b.property_id = p.property_id
    WHERE b.user_id = $user_id
");

// Get messages
$messages = $conn->query("
    SELECT m.message, m.sent_at, 
           (SELECT full_name FROM users WHERE user_id = m.receiver_id) AS receiver_name,
           (SELECT full_name FROM users WHERE user_id = m.sender_id) AS sender_name
    FROM messages m
    WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
");

// Get maintenance requests
$requests = $conn->query("
    SELECT r.request_id, r.description, r.status, r.requested_at, p.title 
    FROM maintenance_requests r
    JOIN properties p ON r.property_id = p.property_id
    WHERE r.tenant_id = $user_id
");

// Get payment history
$payments = $conn->query("
    SELECT p.payment_id, p.amount, p.payment_date, p.status, l.lease_id, pr.title AS property_title
    FROM payments p
    JOIN leases l ON p.lease_id = l.lease_id
    JOIN properties pr ON l.property_id = pr.property_id
    WHERE l.tenant_id = $user_id
");
?>


<!DOCTYPE html>
<html>
<head>
    <title>User Activity - <?= htmlspecialchars($user['full_name']) ?></title>
    <style>
   /* General body styling */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f1f1f1;
    margin: 0;
    padding: 0;
    color: #333;
}

/* Container to center content */
.container {
    width: 80%;
    margin: 30px auto;
    padding: 30px;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    border-top: 4px solid #007bff;
}

/* Header styling */
h2 {
    font-size: 32px;
    color: #333;
    margin-bottom: 20px;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
    text-align: center;
}

/* Sub-header styling */
h3 {
    font-size: 24px;
    color: #007bff;
    margin-top: 20px;
    margin-bottom: 15px;
    font-weight: 600;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

/* Paragraph and list items styling */
p, ul {
    font-size: 16px;
    line-height: 1.8;
    margin: 0;
}

/* Styling for each list item */
ul {
    list-style-type: none;
    padding: 0;
}

ul li {
    padding: 12px;
    background-color: #f9f9f9;
    margin: 10px 0;
    border-left: 4px solid #007bff;
    display: flex;
    justify-content: space-between;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

/* Styling for no results message */
p.no-results {
    font-style: italic;
    color: #888;
}

/* Specific styling for links */
a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

/* Styling for back button */
a.back-btn {
    display: inline-block;
    margin-top: 30px;
    font-size: 16px;
    padding: 12px 25px;
    background-color: #007bff;
    color: #fff;
    border-radius: 4px;
    text-align: center;
    text-decoration: none;
    font-weight: 600;
}

a.back-btn:hover {
    background-color: #0056b3;
}

/* Table-like display for each section */
.report-section {
    margin-top: 30px;
}

.report-section h3 {
    margin-bottom: 10px;
}

/* Responsive Design: Adjustments for smaller screens */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 20px;
    }

    h2 {
        font-size: 28px;
    }

    h3 {
        font-size: 22px;
    }

    ul li {
        font-size: 14px;
        flex-direction: column;
    }
}


    </style>
</head>
<body>
    <h2>User Activity Report: <?= htmlspecialchars($user['full_name']) ?></h2>

    <h3>Bookings</h3>
    <?php if ($bookings && $bookings->num_rows > 0): ?>
    <ul>
        <?php while($row = $bookings->fetch_assoc()): ?>
            <li><strong><?= htmlspecialchars($row['title']) ?></strong> - Booking Date: <?= $row['booking_date'] ?> (Booking ID: <?= $row['booking_id'] ?>)</li>
        <?php endwhile; ?>
    </ul>
    <?php else: ?>
        <p>No bookings found.</p>
    <?php endif; ?>

    <h3>Messages</h3>
    <?php if ($messages && $messages->num_rows > 0): ?>
    <ul>
        <?php while($row = $messages->fetch_assoc()): ?>
            <li>
                <strong><?= htmlspecialchars($row['sender_name']) ?></strong> to 
                <strong><?= htmlspecialchars($row['receiver_name']) ?></strong>: 
                <?= htmlspecialchars($row['message']) ?> 
                <em>(<?= $row['sent_at'] ?>)</em>
            </li>
        <?php endwhile; ?>
    </ul>
    <?php else: ?>
        <p>No messages found.</p>
    <?php endif; ?>

    <h3>Maintenance Requests</h3>
    <?php if ($requests && $requests->num_rows > 0): ?>
    <ul>
        <?php while($row = $requests->fetch_assoc()): ?>
            <li>
                <strong><?= htmlspecialchars($row['title']) ?></strong> — <?= htmlspecialchars($row['description']) ?> |
                Status: <?= ucfirst($row['status']) ?> |
                Requested At: <?= $row['requested_at'] ?>
            </li>
        <?php endwhile; ?>
    </ul>
    <?php else: ?>
        <p>No maintenance requests found.</p>
    <?php endif; ?>
        <div class="report-section">
        <h3>Payment History</h3>
        <?php if ($payments && $payments->num_rows > 0): ?>
        <ul>
            <?php while($row = $payments->fetch_assoc()): ?>
                <li>
                    <strong><?= htmlspecialchars($row['property_title']) ?></strong> — 
                    Paid <strong>$<?= number_format($row['amount'], 2) ?></strong> 
                    on <?= $row['payment_date'] ?> |
                    Status: <?= ucfirst($row['status']) ?> |
                    Lease ID: <?= $row['lease_id'] ?>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php else: ?>
            <p class="no-results">No payment records found.</p>
        <?php endif; ?>
    </div>

    <p><a href="manage_users.php">← Back to Manage Users</a></p>
</body>
</html>
