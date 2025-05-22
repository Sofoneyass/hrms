```html
<?php
require_once 'db_connection.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$tenant_id = $_SESSION['user_id'];

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    
    // Start transaction for atomic updates
    $conn->begin_transaction();
    try {
        // Update booking status to cancelled
        $cancel_sql = "UPDATE bookings SET status = 'cancelled' 
                       WHERE booking_id = ? AND tenant_id = ? AND status = 'pending'";
        $stmt = $conn->prepare($cancel_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param("ss", $booking_id, $tenant_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Get property_id from booking
            $property_sql = "SELECT property_id FROM bookings WHERE booking_id = ?";
            $property_stmt = $conn->prepare($property_sql);
            $property_stmt->bind_param("s", $booking_id);
            $property_stmt->execute();
            $property_result = $property_stmt->get_result();
            $property = $property_result->fetch_assoc();
            $property_stmt->close();
            
            // Update property status to available
            $update_property_sql = "UPDATE properties SET status = 'available' 
                                   WHERE property_id = ?";
            $update_stmt = $conn->prepare($update_property_sql);
            $update_stmt->bind_param("s", $property['property_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            $conn->commit();
            $success_message = "Reservation cancelled successfully! The property is now available.";
        } else {
            $conn->rollback();
            $error_message = "Failed to cancel reservation or already processed.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error cancelling reservation: " . $e->getMessage();
        error_log($error_message);
    }
}

// Fetch reserved properties (exclude cancelled bookings)
$sql = "SELECT b.booking_id, b.property_id, p.title, p.location, 
               p.price_per_month, b.start_date, b.end_date, b.status,
               (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS photo_url
        FROM bookings b
        JOIN properties p ON b.property_id = p.property_id
        WHERE b.tenant_id = ? AND b.status != 'cancelled'
        ORDER BY b.start_date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("s", $tenant_id); // UUID as string
$stmt->execute();
$reserved_properties = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reserved Properties | JIGJIGAHOMES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary: #4A6FA5; /* Ethiopian blue */
            --glass-bg: rgba(255, 255, 255, 0.15);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --text: #333;
            --dark-bg: #121212;
            --dark-text: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #121212 0%, #1e1e1e 100%);
            color: var(--dark-text);
        }

        .reserved-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        h1 {
            text-align: center;
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        [data-theme="dark"] h1 {
            color: #8ab4f8;
        }

        .reserved-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .reserved-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        [data-theme="dark"] .reserved-card {
            background: rgba(30, 41, 59, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .reserved-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .property-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 15px;
            loading: lazy;
        }

        .reserved-card h3 {
            margin: 0 0 10px;
            color: var(--primary);
            font-size: 1.4rem;
        }

        [data-theme="dark"] .reserved-card h3 {
            color: #8ab4f8;
        }

        .reserved-card p {
            margin: 8px 0;
            color: inherit;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            display: inline-block;
            margin-right: 10px;
        }

        .pending { background-color: rgba(254, 243, 199, 0.7); color: #92400e; }
        .confirmed { background-color: rgba(209, 250, 229, 0.7); color: #065f46; }
        .cancelled { background-color: rgba(254, 226, 226, 0.7); color: #991b1b; }
        .completed { background-color: rgba(219, 234, 254, 0.7); color: #1e40af; }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            flex: 1;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #3a5a8c;
        }

        .btn-danger {
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 1rem 0;
            position: relative;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.15);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            background: var(--glass-bg);
            border-radius: 16px;
            backdrop-filter: blur(12px);
        }

        [data-theme="dark"] .empty-state {
            background: rgba(30, 41, 59, 0.3);
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .reserved-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<?php require_once 'header.php'; ?>

<div class="reserved-container">
    <h1>My Reserved Properties</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success" role="alert" aria-live="assertive">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-error" role="alert" aria-live="assertive">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($reserved_properties->num_rows > 0): ?>
        <div class="reserved-grid">
            <?php while ($row = $reserved_properties->fetch_assoc()): ?>
                <div class="reserved-card">
                    <img src="<?= htmlspecialchars($row['photo_url'] ?? 'images/placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($row['title']) ?>" 
                         class="property-image" loading="lazy">
                    
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                    <p><strong>Price:</strong> ETB <?= number_format($row['price_per_month'], 2) ?> / month</p>
                    <p><strong>Period:</strong> 
                        <?= date('M j, Y', strtotime($row['start_date'])) ?> - 
                        <?= date('M j, Y', strtotime($row['end_date'])) ?>
                    </p>
                    
                    <p>
                        <strong>Status:</strong>
                        <span class="status-badge <?= strtolower($row['status']) ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </p>
                    
                    <div class="action-buttons">
                        <a href="property_detail.php?id=<?= $row['property_id'] ?>" 
                           class="btn btn-primary" 
                           aria-label="View details for <?= htmlspecialchars($row['title']) ?>">
                            View Property
                        </a>
                        
                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                <button type="submit" name="cancel_booking" 
                                        class="btn btn-danger" 
                                        aria-label="Cancel reservation for <?= htmlspecialchars($row['title']) ?>">
                                    Cancel
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No Reservations Found</h3>
            <p>You haven't made any property reservations yet.</p>
            <a href="properties.php" class="btn btn-primary" aria-label="Browse available properties">Browse Properties</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

<script>
    // Enhance alert dismissal (optional)
    document.addEventListener('DOMContentLoaded', () => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });
</script>
</body>
</html>
<?php 
$stmt->close();
$conn->close();
?>
```