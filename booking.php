<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['user_id'];

// Clean up: Expire old reservations older than 3 days
$conn->query("
    UPDATE bookings SET status = 'expired'
    WHERE status = 'pending' AND booking_date < (NOW() - INTERVAL 3 DAY)
");

$conn->query("
    UPDATE properties SET status = 'available'
    WHERE property_id IN (
        SELECT property_id FROM bookings
        WHERE status = 'expired'
    )
");

// Validate property ID
if (!isset($_GET['property_id'])) {
    die("Property ID is required.");
}
$property_id = intval($_GET['property_id']);

// Fetch reserved property
$property_sql = "SELECT * FROM properties WHERE property_id = ? AND status = 'reserved'";
$property_stmt = $conn->prepare($property_sql);
if (!$property_stmt) {
    die("Prepare failed: " . $conn->error);
}
$property_stmt->bind_param("i", $property_id);
$property_stmt->execute();
$property_result = $property_stmt->get_result();
$property = $property_result->fetch_assoc();

if (!$property) {
    die("Property not found or not currently reserved.");
}

// Handle booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if already booked by the same tenant
    $check_sql = "SELECT * FROM bookings WHERE tenant_id = ? AND property_id = ? AND status = 'pending'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $tenant_id, $property_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result();

    if ($existing->num_rows > 0) {
        echo "<script>alert('You have already submitted a booking for this property.'); window.location.href='tenant_dashboard.php';</script>";
        exit();
    }

    $booking_date = date("Y-m-d");
    $status = 'pending';

    $insert_sql = "INSERT INTO bookings (property_id, tenant_id, booking_date, status) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $insert_stmt->bind_param("iiss", $property_id, $tenant_id, $booking_date, $status);
    if ($insert_stmt->execute()) {
        echo "<script>alert('Booking request submitted successfully! It will expire in 3 days if not confirmed.'); window.location.href='tenant_dashboard.php';</script>";
        exit();
    } else {
        echo "Booking failed: " . $insert_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Property</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f2f2f2; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; }
        h2 { text-align: center; }
        .property { margin-bottom: 20px; }
        label, p { font-weight: bold; }
        button { padding: 10px 20px; background-color: #007bff; border: none; color: white; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
<div class="container">
    <h2>Book Property</h2>

    <div class="property">
        <p><strong>Description:</strong> <?= htmlspecialchars($property['description']) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($property['location']) ?>, Zone <?= htmlspecialchars($property['zone']) ?>, Kebele <?= htmlspecialchars($property['kebele']) ?></p>
        <p><strong>Bedrooms:</strong> <?= $property['bedrooms'] ?></p>
        <p><strong>Bathrooms:</strong> <?= $property['bathrooms'] ?></p>
        <p><strong>Price Per Month:</strong> $<?= $property['price_per_month'] ?></p>
    </div>

    <form method="post">
        <p>Note: Booking will expire in <strong>3 days</strong> if not confirmed.</p>
        <button type="submit">Confirm Booking</button>
    </form>
</div>
</body>
</html>
