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

// Occupancy rate (percentage of rented properties)
$occupancy_rate = $total_properties > 0 ? round(($active_leases / $total_properties) * 100, 1) : 0;

// Pending maintenance requests
$query = "SELECT COUNT(*) as total FROM maintenance_requests mr 
          JOIN properties p ON mr.property_id = p.property_id 
          WHERE p.owner_id = ? AND mr.status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$pending_maintenance = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Recent bookings (last 30 days)
$query = "SELECT COUNT(*) as total 
          FROM bookings b 
          JOIN properties p ON b.property_id = p.property_id 
          WHERE p.owner_id = ? 
            AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$recent_bookings = $stmt->get_result()->fetch_assoc()['total'];
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

// Property performance (revenue per property, top 5)
$query = "SELECT p.property_id, p.title, SUM(pm.amount_paid) as revenue 
          FROM properties p 
          LEFT JOIN leases l ON p.property_id = l.property_id 
          LEFT JOIN payments pm ON l.lease_id = pm.lease_id 
          WHERE p.owner_id = ? 
          GROUP BY p.property_id, p.title 
          ORDER BY revenue DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$property_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$performance_labels = array_column($property_performance, 'title');
$performance_data = array_column($property_performance, 'revenue');

// Property locations for map
$query = "SELECT 
            p.property_id, 
            p.title, 
            p.status,
            l.wereda,
            l.kebele 
          FROM properties p
          LEFT JOIN locations l ON p.location = l.location_id
          WHERE p.owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$properties_locations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Booking timeline (last 90 days for date filter flexibility)
$booking_data = [];
$booking_labels = [];
$booking_raw_data = [];
$start_date = date('Y-m-d', strtotime('-90 days'));
$end_date = date('Y-m-d');
for ($i = 90; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $booking_labels[] = date('d M', strtotime($date));
    $query = "SELECT COUNT(*) as total FROM bookings b 
              JOIN properties p ON b.property_id = p.property_id 
              WHERE p.owner_id = ? AND DATE(b.booking_date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $owner_id, $date);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $booking_data[] = $count;
    $booking_raw_data[] = ['date' => $date, 'count' => $count];
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JIGJIGAHOMES Owner Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
        }

        .card h3 {
            font-size: 14px;
            color: #FFD700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .card p {
            font-size: 22px;
            font-weight: bold;
            color: #ffffff;
        }

        .card .subtext {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        .charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }

        .chart-container:hover {
            transform: translateY(-3px);
        }

        .chart-container h3 {
            font-size: 16px;
            color: #FFD700;
            margin-bottom: 15px;
            text-align: center;
        }

        .date-filter-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        .date-filter-container label {
            color: #FFD700;
            font-size: 14px;
        }

        .date-filter-container input[type="date"] {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff;
            padding: 8px;
            border-radius: 5px;
            font-size: 14px;
        }

        .date-filter-container input[type="date"]:focus {
            outline: 2px solid #FFD700;
        }

        .date-filter-container button {
            background: #FFD700;
            border: none;
            color: #1a2a44;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .date-filter-container button:hover {
            background: #e6c200;
        }

        @media (max-width: 1024px) {
            .charts {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            .metric-cards {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            .metric-cards {
                grid-template-columns: 1fr;
            }
            .date-filter-container {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>JIGJIGAHOMES</h2>
        <a href="owner_dashboard.php" class="active">Dashboard</a>
        <a href="my_properties.php">My Properties</a>
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
                <button class="profile-btn" aria-label="Profile menu">Profile</button>
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
                <p class="subtext">Managed Assets</p>
            </div>
            <div class="card">
                <h3>Active Leases</h3>
                <p><?php echo $active_leases; ?></p>
                <p class="subtext">Current Tenants</p>
            </div>
            <div class="card">
                <h3>Total Revenue</h3>
                <p>ETB <?php echo number_format($total_revenue, 2); ?></p>
                <p class="subtext">Last 12 Months</p>
            </div>
            <div class="card">
                <h3>Occupancy Rate</h3>
                <p><?php echo $occupancy_rate; ?>%</p>
                <p class="subtext">Rented Properties</p>
            </div>
            <div class="card">
                <h3>Pending Maintenance</h3>
                <p><?php echo $pending_maintenance; ?></p>
                <p class="subtext">Open Requests</p>
            </div>
            <div class="card">
                <h3>Recent Bookings</h3>
                <p><?php echo $recent_bookings; ?></p>
                <p class="subtext">Last 30 Days</p>
            </div>
        </div>
        <div class="charts">
    <div class="chart-container">
        <h3>Property Status Distribution</h3>
        <canvas id="statusChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Revenue Trend (Last 6 Months)</h3>
        <canvas id="revenueChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Top Performing Properties</h3>
        <canvas id="performanceChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Property Locations</h3>
        <div id="map"></div>
    </div>
    <div class="chart-container">
        <h3>Booking Activity</h3>
        <div class="date-filter-container">
            <label for="startDate">Start Date:</label>
            <input type="date" id="startDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            <label for="endDate">End Date:</label>
            <input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
            <button onclick="updateBookingChart()">Apply</button>
        </div>
        <canvas id="bookingChart"></canvas>
    </div>
</div>
    </div>
   <script>
    // Sidebar toggle for mobile
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }

    // Property Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Available', 'Rented', 'Maintenance'],
            datasets: [{
                data: [<?php echo $status_data['Available']; ?>, <?php echo $status_data['Rented']; ?>, <?php echo $status_data['Maintenance']; ?>],
                backgroundColor: ['#FFD700', '#4CAF50', '#FF6347'],
                borderColor: '#ffffff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#ffffff',
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1a2a44',
                    bodyColor: '#1a2a44',
                    borderColor: '#FFD700',
                    borderWidth: 1
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });

    // Revenue Trend Area Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Revenue (ETB)',
                data: <?php echo json_encode($revenue_data); ?>,
                borderColor: '#FFD700',
                backgroundColor: 'rgba(255, 215, 0, 0.3)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#FFD700',
                pointHoverBackgroundColor: '#FF6347'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#ffffff',
                        callback: function(value) {
                            return 'ETB ' + value;
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#ffffff'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#ffffff'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1a2a44',
                    bodyColor: '#1a2a44',
                    callbacks: {
                        label: function(context) {
                            return `ETB ${context.parsed.y.toFixed(2)}`;
                        }
                    }
                }
            }
        }
    });

    // Property Performance Bar Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($performance_labels); ?>,
            datasets: [{
                label: 'Revenue (ETB)',
                data: <?php echo json_encode($performance_data); ?>,
                backgroundColor: 'rgba(76, 175, 80, 0.7)',
                borderColor: '#4CAF50',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#ffffff',
                        callback: function(value) {
                            return 'ETB ' + value;
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#ffffff',
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#ffffff'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1a2a44',
                    bodyColor: '#1a2a44',
                    callbacks: {
                        label: function(context) {
                            return `ETB ${context.parsed.y.toFixed(2)}`;
                        }
                    }
                }
            }
        }
    });

    // Property Locations Map
    const map = L.map('map').setView([9.1450, 40.4897], 6); // Default to Ethiopia's coordinates
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    const statusColors = {
        'Available': '#FFD700',
        'Rented': '#4CAF50',
        'Maintenance': '#FF6347'
    };

    <?php foreach ($properties_locations as $property): ?>
        <?php if (!empty($property['wereda']) && !empty($property['kebele'])): ?>
            // Note: wereda and kebele are not coordinates; geocoding would be needed
            // For now, skip map markers due to missing latitude/longitude
        <?php endif; ?>
    <?php endforeach; ?>

    // Booking Activity Bar Chart with Date Filter
    let bookingChart;
    const bookingCtx = document.getElementById('bookingChart').getContext('2d');
    const rawBookingData = <?php echo json_encode($booking_raw_data); ?>;
    const allLabels = <?php echo json_encode($booking_labels); ?>;
    const allData = <?php echo json_encode($booking_data); ?>;

    function updateBookingChart() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate || startDate > endDate) {
            alert('Please select a valid date range.');
            return;
        }

        // Filter data based on date range
        const filteredData = [];
        const filteredLabels = [];
        rawBookingData.forEach(item => {
            if (item.date >= startDate && item.date <= endDate) {
                filteredLabels.push(new Date(item.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }));
                filteredData.push(item.count);
            }
        });

        if (filteredData.length === 0) {
            alert('No bookings found in the selected date range.');
            return;
        }

        if (bookingChart) bookingChart.destroy();
        bookingChart = new Chart(bookingCtx, {
            type: 'bar',
            data: {
                labels: filteredLabels,
                datasets: [{
                    label: 'Bookings',
                    data: filteredData,
                    backgroundColor: '#FF6347',
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#ffffff' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                    x: { ticks: { color: '#ffffff', maxRotation: 45, minRotation: 45 }, grid: { display: false } }
                },
                plugins: {
                    legend: { labels: { color: '#ffffff' } },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1a2a44',
                        bodyColor: '#1a2a44'
                    }
                }
            }
        });
    }

    // Initialize booking chart with default 30-day range
    updateBookingChart();
</script>
</body>
</html>