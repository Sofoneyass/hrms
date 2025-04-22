<?php
require_once 'db_connection.php';
session_start();

// Ensure the user is logged in and the property id is available
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

// Use standard property ID check format
if (!isset($_GET['id'])) {
    die("Property ID is required.");
}
$property_id = intval($_GET['id']);

$tenant_id = $_SESSION['user_id']; // User who is logged in

// Fetch the property details
$sql = "SELECT * FROM properties WHERE property_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

// Check if the property exists and is available
if ($property && $property['status'] != 'reserved') {
    // Proceed with the reservation process
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Check if already reserved during selected period
        $check_booking_sql = "SELECT * FROM bookings 
                              WHERE property_id = ? 
                              AND status = 'confirmed' 
                              AND (start_date <= ? AND end_date >= ?)";
        $stmt = $conn->prepare($check_booking_sql);
        $stmt->bind_param("iss", $property_id, $start_date, $end_date);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "This property is already reserved for the selected dates.";
        } else {
            // Insert reservation
            $insert_booking_sql = "INSERT INTO bookings (tenant_id, property_id, start_date, end_date, status) 
                                   VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($insert_booking_sql);
            $stmt->bind_param("iiss", $tenant_id, $property_id, $start_date, $end_date);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Update property status
                $update_property_sql = "UPDATE properties SET status = 'reserved' WHERE property_id = ?";
                $stmt = $conn->prepare($update_property_sql);
                $stmt->bind_param("i", $property_id);
                $stmt->execute();

                header("Location: reserved_properties.php?property_id=" . $property_id);
                exit();
            } else {
                $error_message = "There was an issue reserving the property. Please try again.";
            }
        }
    }
} else {
    $error_message = "This property is either not available or already reserved.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reserve Property</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="reservation-container">
        <h1>Reserve Property: <?= htmlspecialchars($property['title']) ?></h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>

        <form action="reserve_property.php?id=<?= $property_id ?>" method="POST">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" id="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" id="end_date" required>
            </div>
            <button type="submit">Reserve</button>
        </form>
    </div>
</body>
</html>
