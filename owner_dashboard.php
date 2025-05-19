<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

$owner_id = $_SESSION['user_id'];

// Fetch dashboard metrics
// Total properties
$query = "SELECT COUNT(*) as total FROM properties WHERE owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$total_properties = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Active leases
$query = "SELECT COUNT(*) as total FROM leases l JOIN properties p ON l.property_id = p.property_id 
          WHERE p.owner_id = ? AND l.status = 'active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$active_leases = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total revenue (last 12 months)
$query = "SELECT SUM(amount_paid) as total FROM payments pm 
          JOIN leases l ON pm.lease_id = l.lease_id 
          JOIN properties p ON l.property_id = p.property_id 
          WHERE p.owner_id = ? AND pm.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Property status distribution
$status_data = ['Available' => 0, 'Rented' => 0, 'Maintenance' => 0];
$query = "SELECT status, COUNT(*) as count FROM properties WHERE owner_id = ? GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($status_data[$row['status']])) {
        $status_data[$row['status']] = $row['count'];
    }
}
$stmt->close();

// Revenue trend (monthly, last 6 months)
$revenue_data = [];
$labels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $labels[] = date('M Y', strtotime($month));
    $query = "SELECT SUM(amount_paid) as total FROM payments pm 
              JOIN leases l ON pm.lease_id = l.lease_id 
              JOIN properties p ON l.property_id = p.property_id 
              WHERE p.owner_id = ? AND DATE_FORMAT(pm.payment_date, '%Y-%m') = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $owner_id, $month);
    $stmt->execute();
    $revenue_data[] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JIGJIGAHOMES Owner Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a2a44 0%, #2a4066 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar h2 {
            color: #FFD700;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: #ffffff;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #FFD700;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            background: none;
            border: none;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 5px;
            min-width: 150px;
            z-index: 1;
        }

        .dropdown-content a {
            color: #ffffff;
            padding: 12px 16px;
            display: block;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background: rgba(255, 215, 0, 0.2);
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .metric-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            font-size: 16px;
            color: #FFD700;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
        }

        .charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            .charts {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .header {
                position: relative;
            }
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 24px;
                color: #FFD700;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>JIGJIGAHOMES</h2>
        <a href="owner_dashboard.php" class="active">Dashboard</a>
        <a href="owner_manage_properties.php">Manage Properties</a>
        <a href="manage_leases.php">Manage Leases</a>
        <a href="view_payments.php">View Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
            <h1>Owner Dashboard</h1>
            <div class="profile-dropdown">
                <button class="profile-btn">Profile</button>
                <div class="dropdown-content">
                    <a href="profile.php">Edit Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <div class="metric-cards">
            <div class="card">
                <h3>Total Properties</h3>
                <p><?php echo $total_properties; ?></p>
            </div>
            <div class="card">
                <h3>Active Leases</h3>
                <p><?php echo $active_leases; ?></p>
            </div>
            <div class="card">
                <h3>Total Revenue</h3>
                <p>$<?php echo number_format($total_revenue, 2); ?></p>
            </div>
        </div>
        <div class="charts">
            <div class="chart-container">
                <h3>Property Status</h3>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Revenue Trend</h3>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // Property Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Available', 'Rented', 'Maintenance'],
                datasets: [{
                    data: [<?php echo $status_data['Available']; ?>, 
                           <?php echo $status_data['Rented']; ?>, 
                           <?php echo $status_data['Maintenance']; ?>],
                    backgroundColor: ['#FFD700', '#4CAF50', '#FF6347'],
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#ffffff' } }
                }
            }
        });

        // Revenue Trend Line Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: '#FFD700',
                    backgroundColor: 'rgba(255, 215, 0, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#ffffff' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                    x: { ticks: { color: '#ffffff' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } }
                },
                plugins: {
                    legend: { labels: { color: '#ffffff' } }
                }
            }
        });
    </script>
</body>
</html>