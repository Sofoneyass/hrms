<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit;
}

$tenant_id = $_SESSION['user_id'];

// Fetch booked properties with their photo, using property_id
$sql = "SELECT b.*, p.property_id, p.title, p.location, p.address_detail, p.price_per_month, 
               (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS photo
        FROM bookings b
        JOIN properties p ON b.property_id = p.property_id
        WHERE b.tenant_id = ?
        ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);

// Check if the preparation of the query is successful
if ($stmt === false) {
    // Output the error from MySQL
    die('SQL prepare error: ' . $conn->error);
}

$stmt->bind_param("i", $tenant_id);
$stmt->execute();

// Check if execution is successful
$result = $stmt->get_result();
if ($result === false) {
    die('Error fetching data: ' . $stmt->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .bookings-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .bookings-title {
            text-align: center;
            font-size: 32px;
            margin-bottom: 30px;
            color: #333;
        }

        .booking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .booking-card {
            background-color: #fefefe;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
        }

        .booking-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .booking-details {
            padding: 15px;
        }

        .booking-details h3 {
            margin: 0 0 10px;
            font-size: 22px;
            color: #222;
        }

        .booking-details p {
            margin: 5px 0;
            color: #666;
        }

        .status-tag {
            display: inline-block;
            padding: 5px 10px;
            margin-top: 10px;
            font-size: 14px;
            border-radius: 5px;
            background-color: #2196F3;
            color: white;
        }
    </style>
</head>
<body>

<div class="bookings-container">
    <h1 class="bookings-title">My Booked Properties</h1>

    <div class="booking-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="booking-card">
                    <img src="<?= $row['photo'] ? $row['photo'] : 'uploads/default.png' ?>" alt="Property Image">
                    <div class="booking-details">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['location']) ?> — <?= htmlspecialchars($row['address_detail']) ?></p>
                        <p><strong>From:</strong> <?= $row['start_date'] ?> <strong>To:</strong> <?= $row['end_date'] ?></p>
                        <p><strong>Price:</strong> BIRR <?= number_format($row['price_per_month'], 2) ?> / month</p>
                        <span class="status-tag"><?= ucfirst($row['status']) ?></span>
                        <a href="property_detail.php?id=<?= $row['property_id'] ?>" class="view-button">View Property</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't booked any properties yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
