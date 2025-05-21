<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$tenant_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'tenant'; // Default to tenant if role is not set

// UUID-safe check
if (!isset($_GET['id'])) {
    die("Property ID is required.");
}
$property_id = $_GET['id'];

// 🔁 Cleanup expired reservations older than 3 days
$conn->query("
    UPDATE bookings SET status = 'expired'
    WHERE status = 'pending' AND booking_date < NOW() - INTERVAL 3 DAY
");

$conn->query("
    UPDATE properties SET status = 'available'
    WHERE property_id IN (
        SELECT property_id FROM bookings
        WHERE status = 'expired'
    )
");

// 🔎 Fetch property info
$stmt = $conn->prepare("SELECT * FROM properties WHERE property_id = ?");
$stmt->bind_param("s", $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();

// 🔎 Fetch pending booking for this property (for owner actions)
$pending_booking = null;
if ($user_role === 'owner') {
    $stmt = $conn->prepare("
        SELECT * FROM bookings 
        WHERE property_id = ? AND status = 'pending'
        LIMIT 1
    ");
    $stmt->bind_param("s", $property_id);
    $stmt->execute();
    $pending_booking = $stmt->get_result()->fetch_assoc();
}

if (!$property || $property['status'] === 'reserved') {
    $error_message = "This property is currently reserved and cannot be booked at this time.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_role === 'tenant') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Ensure selected period is within 3 days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $start->diff($end)->days;
    if ($diff > 3) {
        $error_message = "Reservation cannot exceed 3 days.";
    } else {
        // Check existing bookings
        $stmt = $conn->prepare("
            SELECT * FROM bookings 
            WHERE property_id = ? AND status = 'confirmed' 
            AND (start_date <= ? AND end_date >= ?)
        ");
        $stmt->bind_param("sss", $property_id, $end_date, $start_date);
        $stmt->execute();
        $conflicts = $stmt->get_result();

        if ($conflicts->num_rows > 0) {
            $error_message = "This property is already reserved for the selected dates.";
        } else {
            // Insert reservation
            $stmt = $conn->prepare("
                INSERT INTO bookings (tenant_id, property_id, start_date, end_date, status, booking_date)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->bind_param("ssss", $tenant_id, $property_id, $start_date, $end_date);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Update property status
                $stmt = $conn->prepare("UPDATE properties SET status = 'reserved' WHERE property_id = ?");
                $stmt->bind_param("s", $property_id);
                $stmt->execute();

                header("Location: reserved_properties.php?property_id=" . $property_id);
                exit;
            } else {
                $error_message = "There was an issue reserving the property. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reserve Property</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Icons (Lucide) -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #c2e9fb, #a1c4fd);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .reservation-container {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 2rem;
            padding: 2rem 2.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            color: #fff;
            text-align: center;
        }

        .reservation-container h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .error-message {
            background: rgba(255, 0, 0, 0.15);
            color: #ff4e4e;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(255, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.95rem;
        }

        input[type="date"] {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 0.75rem;
            background-color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            color: #333;
        }

        input[type="date"]:focus {
            outline: 2px solid #6c63ff;
        }

        button {
            width: 100%;
            background: linear-gradient(to right, #6c63ff, #5d47ff);
            border: none;
            color: white;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(92, 71, 255, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .approve-btn {
            background: linear-gradient(to right, #28a745, #218838);
        }

        .reject-btn {
            background: linear-gradient(to right, #dc3545, #c82333);
        }

        .approve-btn:hover, .reject-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
        }

        .info-message {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 500px) {
            .reservation-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="reservation-container">
    <h1>Reserve Property: <?= htmlspecialchars($property['title']) ?></h1>

    <?php if (isset($error_message)): ?>
        <div class="error-message">
            <i data-lucide="alert-circle"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php elseif ($user_role === 'owner' && $pending_booking): ?>
        <div class="error-message">
            <i data-lucide="info"></i>
            This property has a pending reservation. Please approve or reject the booking.
        </div>
        <div class="action-buttons">
            <form action="owner_manage_booking_status.php" method="POST">
                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($pending_booking['booking_id']) ?>">
                <input type="hidden" name="property_id" value="<?= htmlspecialchars($property_id) ?>">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? bin2hex(random_bytes(32))) ?>">
                <button type="submit" class="approve-btn">Approve</button>
            </form>
            <form action="owner_manage_booking_status.php" method="POST">
                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($pending_booking['booking_id']) ?>">
                <input type="hidden" name="property_id" value="<?= htmlspecialchars($property_id) ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? bin2hex(random_bytes(32))) ?>">
                <button type="submit" class="reject-btn">Reject</button>
            </form>
        </div>
    <?php else: ?>
        <form action="reserve_property.php?id=<?= htmlspecialchars($property_id) ?>" method="POST">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" required>
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" required>
            </div>

            <button type="submit">Reserve</button>
            <p class="info-message">
                Your reservation will be held for <strong>3 days</strong>. If not confirmed within this period,
                the property will automatically become available again.
            </p>
        </form>
    <?php endif; ?>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>