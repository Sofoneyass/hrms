<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$tenant_id = $_SESSION['user_id'];

$sql = "SELECT b.booking_id, b.property_id, p.title, p.location, p.price_per_month, b.start_date, b.end_date, b.status
        FROM bookings b
        JOIN properties p ON b.property_id = p.property_id
        WHERE b.tenant_id = ?
        ORDER BY b.start_date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$reserved_properties = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reserved Properties</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .reserved-container {
            max-width: 900px;
            margin: auto;
            padding: 30px;
        }
        .reserved-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .reserved-card h3 {
            margin: 0 0 10px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .pending { background-color: #fef3c7; color: #92400e; }
        .approved { background-color: #d1fae5; color: #065f46; }
        .rejected { background-color: #fee2e2; color: #991b1b; }

        .book-button {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .book-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="reserved-container">
    <h1>My Reserved Properties</h1>

    <?php if ($reserved_properties->num_rows > 0): ?>
        <?php while ($row = $reserved_properties->fetch_assoc()): ?>
            <div class="reserved-card">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><strong>Address:</strong> <?= htmlspecialchars($row['location']) ?></p>
                <p><strong>Price:</strong> BIRR <?= number_format($row['price_per_month'], 2) ?> / month</p>
                <p><strong>Reservation Period:</strong> <?= $row['start_date'] ?> to <?= $row['end_date'] ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge <?= strtolower($row['status']) ?>">
                        <?= ucfirst($row['status']) ?>
                    </span>
                </p>

                <a class="book-button" href="booking.php?property_id=<?= $row['property_id'] ?>">Book Now</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>You have no reserved properties.</p>
    <?php endif; ?>
</div>

</body>
</html>
