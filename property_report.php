<?php
include 'db_connection.php';

if (!isset($_GET['property_id'])) {
    die("Property ID is required.");
}

$property_id = intval($_GET['property_id']);

// Date filter
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$property_query = $conn->prepare("SELECT * FROM properties WHERE property_id = ?");
$property_query->bind_param("i", $property_id);
$property_query->execute();
$property = $property_query->get_result()->fetch_assoc();

// Booking history
$booking_sql = "SELECT * FROM bookings WHERE property_id = ?";
$params = [$property_id];
$types = "i";

if ($start_date && $end_date) {
    $booking_sql .= " AND booking_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$booking_stmt = $conn->prepare($booking_sql);
$booking_stmt->bind_param($types, ...$params);
$booking_stmt->execute();
$bookings = $booking_stmt->get_result();

// Payments
$payment_stmt = $conn->prepare("
    SELECT pay.* 
    FROM payments AS pay
    INNER JOIN leases AS l ON pay.lease_id = l.lease_id
    WHERE l.property_id = ?
    ORDER BY pay.payment_date DESC
");
$payment_stmt->bind_param("i", $property_id);
$payment_stmt->execute();
$payments = $payment_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Printable Property Report</title>
    
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>    

    <style>
        :root {
            --primary: #10B981;
            --secondary: #FBBF24;
            --accent: #06B6D4;
            --dark: #1F2937;
            --darker: #111827;
            --text-light: rgba(255,255,255,0.9);
            --text-muted: rgba(255,255,255,0.7);
            --card-bg: rgba(31, 41, 55, 0.8);
            --card-border: rgba(255, 255, 255, 0.15);
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: white;
            color: #000;
            padding: 2rem;
        }

        h1, h2 {
            color: #111827;
        }

        .card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
        }

        th {
            background: #e5e7eb;
        }

        .filter-form {
            margin-bottom: 1rem;
        }

        .filter-form input {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            background: #06B6D4;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.85;
        }

        .label {
            font-weight: bold;
            color: #333;
        }

        .print-btn {
            float: right;
            margin-bottom: 1rem;
            background: #10B981;
        }

        @media print {
            .print-btn, .filter-form {
                display: none;
            }

            body {
                background: white;
                color: black;
                padding: 0;
                margin: 0;
            }

            .card {
                border: none;
                box-shadow: none;
                page-break-inside: avoid;
            }

            h1, h2 {
                color: black;
            }

            table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<button class="btn print-btn" onclick="window.print()">🖨️ Print Report</button>

<h1>Property Report</h1>

<div class="card">
    <h2><?= htmlspecialchars($property['title']) ?></h2>
    <p><span class="label">Zone:</span> <?= htmlspecialchars($property['zone'] ?? 'N/A') ?></p>
    <p><span class="label">Woreda:</span> <?= htmlspecialchars($property['location'] ?? 'N/A') ?></p>
    <p><span class="label">Kebele:</span> <?= htmlspecialchars($property['kebele'] ?? 'N/A') ?></p>
    <p><span class="label">Price per Month:</span> $<?= number_format($property['price_per_month'], 2) ?></p>
    <p><span class="label">Status:</span> <?= ucfirst($property['status']) ?></p>
    <p><span class="label">Created At:</span> <?= $property['created_at'] ?></p>
</div>

<div class="card">
    <h2>Booking History</h2>
    <form method="GET" class="filter-form">
    <input type="hidden" name="property_id" value="<?= $property_id ?>">
    <label for="start_date">From:</label>
    <input type="text" id="start_date" name="start_date" class="datepicker" value="<?= htmlspecialchars($start_date) ?>">
    <label for="end_date">To:</label>
    <input type="text" id="end_date" name="end_date" class="datepicker" value="<?= htmlspecialchars($end_date) ?>">
    <button class="btn" type="submit">Filter</button>
</form>


    <?php if ($bookings->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>User ID</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($b = $bookings->fetch_assoc()): ?>
                <tr>
                    <td><?= $b['booking_id'] ?></td>
                    <td><?= $b['user_id'] ?></td>
                    <td><?= $b['booking_date'] ?></td>
                    <td><?= ucfirst($b['status']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No bookings found in selected date range.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Payments</h2>
    <?php if ($payments->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>User ID</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($p = $payments->fetch_assoc()): ?>
                <tr>
                    <td><?= $p['payment_id'] ?></td>
                    <td><?= $p['user_id'] ?></td>
                    <td>$<?= number_format($p['amount_paid'], 2) ?></td>
                    <td><?= ucfirst($p['payment_method']) ?></td>
                    <td><?= $p['payment_date'] ?></td>
                    <td><?= ucfirst($p['payment_status']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No payments found for this property.</p>
    <?php endif; ?>
</div>

</body>
</html>
