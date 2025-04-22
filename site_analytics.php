<?php
include 'auth_session.php';
require 'db_connection.php';

$adminId = $_SESSION['user_id'];

$allowedTables = ['users', 'properties', 'bookings', 'leases', 'payments'];
$allowedDateFields = ['created_at', 'payment_date', 'lease_start_date', 'booking_date'];
$allowedSumFields = ['amount_paid'];

function getCount(mysqli $conn, string $table): int {
    global $allowedTables;
    if (!in_array($table, $allowedTables)) return 0;
    $sql = "SELECT COUNT(*) AS total FROM `$table`";
    $result = $conn->query($sql);
    return ($result && $row = $result->fetch_assoc()) ? (int)$row['total'] : 0;
}

function getMonthlyData(mysqli $conn, string $table, string $dateField, string $sumField = null): array {
    global $allowedTables, $allowedDateFields, $allowedSumFields;

    if (!in_array($table, $allowedTables) || !in_array($dateField, $allowedDateFields)) {
        return array_fill(0, 6, 0);
    }
    if ($sumField && !in_array($sumField, $allowedSumFields)) {
        return array_fill(0, 6, 0);
    }

    $data = [];
    $sql = $sumField 
        ? "SELECT SUM($sumField) AS total FROM $table WHERE DATE_FORMAT($dateField, '%Y-%m') = ?" 
        : "SELECT COUNT(*) AS total FROM $table WHERE DATE_FORMAT($dateField, '%Y-%m') = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return array_fill(0, 6, 0);

    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt->bind_param("s", $month);
        if (!$stmt->execute()) {
            $data[] = 0;
            continue;
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $data[] = $row['total'] ?? 0;
    }

    $stmt->close();
    return $data;
}

// Generate month labels
$months = array_map(fn($i) => date('M Y', strtotime("-$i months")), range(5, 0));

// Fetch counts
$userCount     = getCount($conn, 'users');
$propertyCount = getCount($conn, 'properties');
$bookingCount  = getCount($conn, 'bookings');
$leaseCount    = getCount($conn, 'leases');
$paymentCount  = getCount($conn, 'payments');

// Fetch trends
$paymentData   = getMonthlyData($conn, 'payments', 'payment_date', 'amount_paid');
$userData      = getMonthlyData($conn, 'users', 'created_at');
$propertyData  = getMonthlyData($conn, 'properties', 'created_at');
$leaseData     = getMonthlyData($conn, 'leases', 'lease_start_date');
$bookingData   = getMonthlyData($conn, 'bookings', 'booking_date');
?>




<!DOCTYPE html>
<html>
<head>
    <title>Admin Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            background: #212529;
            padding: 20px;
            color: white;
        }
        .sidebar a {
            color: #bbb;
            display: block;
            padding: 10px;
            text-decoration: none;
        }
        .sidebar a:hover {
            background: #343a40;
            color: white;
        }
        .content {
            flex-grow: 1;
            padding: 30px;
            background: #f8f9fa;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        canvas {
            max-height: 300px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4>Admin Panel</h4>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_properties.php">Manage Properties</a>
        <a href="site_analytics.php"><strong>Analytics</strong></a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h2>Analytics Overview</h2>
        <div class="btn-group mb-4" role="group" aria-label="Analytics Tabs">
    <button class="btn btn-outline-primary" onclick="showAnalytics('payments')">Payments</button>
    <button class="btn btn-outline-success" onclick="showAnalytics('users')">Users</button>
    <button class="btn btn-outline-warning" onclick="showAnalytics('properties')">Properties</button>
    <button class="btn btn-outline-danger" onclick="showAnalytics('leases')">Leases</button>
    <button class="btn btn-outline-info" onclick="showAnalytics('bookings')">Bookings</button>

</div>

        <div class="row my-4">
            <div class="col-md-2"><div class="card p-3 bg-light text-center"><h6>Users</h6><h4><?= $userCount ?></h4></div></div>
            <div class="col-md-2"><div class="card p-3 bg-light text-center"><h6>Properties</h6><h4><?= $propertyCount ?></h4></div></div>
            <div class="col-md-2"><div class="card p-3 bg-light text-center"><h6>Bookings</h6><h4><?= $bookingCount ?></h4></div></div>
            <div class="col-md-2"><div class="card p-3 bg-light text-center"><h6>Leases</h6><h4><?= $leaseCount ?></h4></div></div>
            <div class="col-md-2"><div class="card p-3 bg-light text-center"><h6>Payments</h6><h4><?= $paymentCount ?></h4></div></div>
        </div>
        
        <div class="mb-3">
    <label for="timeRange" class="form-label">Select Time Range:</label>
    <select id="timeRange" class="form-select w-auto d-inline" onchange="updateCharts()">
        <option value="month" selected>Month</option>
        <option value="day">Day</option>
        <option value="week">Week</option>
        <option value="year">Year</option>
    </select>
</div>

        <div id="analytics-payments" class="card p-4 analytics-section">
    <h5>Monthly Payments Trend</h5>
    <canvas id="paymentChart"></canvas>
</div>

<div id="analytics-users" class="card p-4 analytics-section" style="display: none;">
    <h5>User Registrations (Last 6 Months)</h5>
    <canvas id="userChart"></canvas>
</div>

<div id="analytics-properties" class="card p-4 analytics-section" style="display: none;">
    <h5>New Properties Added (Last 6 Months)</h5>
    <canvas id="propertyChart"></canvas>
</div>

<div id="analytics-leases" class="card p-4 analytics-section" style="display: none;">
    <h5>New Leases Created (Last 6 Months)</h5>
    <canvas id="leaseChart"></canvas>
</div>
<div id="analytics-bookings" class="card p-4 analytics-section" style="display: none;">
    <h5>New Bookings (Last 6 Months)</h5>
    <canvas id="bookingChart"></canvas>
</div>


    </div>

    <script>
    const months = <?= json_encode($months) ?>;

    const analyticsSections = ['payments', 'users', 'properties', 'leases', 'bookings'];

    function showAnalytics(type) {
        analyticsSections.forEach(section => {
            document.getElementById('analytics-' + section).style.display = section === type ? 'block' : 'none';
        });
    }

    new Chart(document.getElementById('paymentChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Payments (₦)',
                data: <?= json_encode($paymentData) ?>,
                backgroundColor: '#007bff'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => '₦' + value
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('userChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'User Signups',
                data: <?= json_encode($userData) ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: '#28a745',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        }
    });

    new Chart(document.getElementById('propertyChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Properties Added',
                data: <?= json_encode($propertyData) ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                borderColor: '#ffc107',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        }
    });

    new Chart(document.getElementById('leaseChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Leases Created',
                data: <?= json_encode($leaseData) ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                borderColor: '#dc3545',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        }
    });

    new Chart(document.getElementById('bookingChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Bookings',
                data: <?= json_encode($bookingData) ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                borderColor: '#17a2b8',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        }
    });
</script>



</body>
</html>
