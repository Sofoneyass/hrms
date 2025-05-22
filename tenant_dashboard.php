<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit;
}

$tenant_id = $_SESSION['user_id'];

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch tenant details
$stmt = $conn->prepare("SELECT full_name, email, phone, profile_image FROM users WHERE user_id = ?");
if (!$stmt) {
    die("Error preparing tenant query: " . $conn->error);
}
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch dashboard metrics
// Total favorites
$query = "SELECT COUNT(*) as total FROM favorites WHERE tenant_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$total_favorites = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Active leases
$query = "SELECT COUNT(*) as total FROM leases WHERE tenant_id = ? AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$active_leases = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total payments (last 12 months)
$query = "SELECT SUM(amount_paid) as total FROM payments pm 
          JOIN leases l ON pm.lease_id = l.lease_id 
          WHERE l.tenant_id = ? AND pm.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$total_payments = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Pending bookings
$query = "SELECT COUNT(*) as total FROM bookings WHERE tenant_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$pending_bookings = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Recent messages (last 30 days)
$query = "SELECT COUNT(*) as total FROM messages 
          WHERE receiver_id = ? AND sent_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$recent_messages = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Lease status distribution
$status_data = ['Active' => 0, 'Expired' => 0, 'Pending' => 0];
$query = "SELECT status, COUNT(*) as count FROM leases WHERE tenant_id = ? GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($status_data[ucfirst($row['status'])])) {
        $status_data[ucfirst($row['status'])] = $row['count'];
    }
}
$stmt->close();

// Payment trend (monthly, last 6 months)
$payment_data = [];
$labels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $labels[] = date('M Y', strtotime($month));
    $query = "SELECT SUM(amount_paid) as total FROM payments pm 
              JOIN leases l ON pm.lease_id = l.lease_id 
              WHERE l.tenant_id = ? AND DATE_FORMAT(pm.payment_date, '%Y-%m') = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $tenant_id, $month);
    $stmt->execute();
    $payment_data[] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
}

// Favorite property types
$query = "SELECT p.title, COUNT(*) as count 
          FROM favorites f 
          JOIN properties p ON f.property_id = p.property_id 
          WHERE f.tenant_id = ? 
          GROUP BY p.title 
          ORDER BY count DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$favorite_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$type_labels = array_column($favorite_types, 'type');
$type_data = array_column($favorite_types, 'count');

// Property locations for map
$query = "SELECT 
            p.property_id, 
            p.title, 
            p.status, 
            p.location AS wereda, 
            p.kebele 
          FROM properties p
          JOIN leases l ON p.property_id = l.property_id
          WHERE l.tenant_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tenant_id);
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
    $query = "SELECT COUNT(*) as total FROM bookings 
              WHERE tenant_id = ? AND DATE(booking_date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $tenant_id, $date);
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
    <title>JIGJIGAHOMES Tenant Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #2a7f62;
            --primary-dark: #1e3c2b;
            --accent: #f0c14b;
            --text: #333333;
            --text-light: #6b7280;
            --bg: #f9fafb;
            --card-bg: rgba(255, 255, 255, 0.95);
            --glass-bg: rgba(255, 255, 255, 0.2);
            --border: #e5e7eb;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --dark-bg: #121212;
            --dark-card-bg: rgba(30, 30, 30, 0.9);
            --dark-glass-bg: rgba(50, 50, 50, 0.3);
            --dark-text: #e4e4e7;
            --dark-text-light: #a1a1aa;
            --dark-border: rgba(255, 255, 255, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Manrope', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            transition: background 0.3s ease, color 0.3s ease;
            overscroll-behavior: none;
        }

        body.dark-mode {
            background: var(--dark-bg);
            color: var(--dark-text);
        }

        body.dark-mode .card {
            background: var(--dark-card-bg);
            border: 1px solid var(--dark-border);
        }

        body.dark-mode .header, body.dark-mode .sidebar {
            background: var(--dark-glass-bg);
            border-color: var(--dark-border);
        }

        body.dark-mode .highlight {
            color: var(--accent);
        }

        body.dark-mode .sidebar a:hover,
        body.dark-mode .sidebar a.active {
            background: var(--primary);
            color: #ffffff;
        }

        body.dark-mode .chart-container {
            background: var(--dark-card-bg);
        }

        /* Particle Background */
        #particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: transparent;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid var(--border);
            transition: width 0.3s ease, transform 0.3s ease;
            z-index: 1001;
        }

        .sidebar h2 {
            color: var(--primary-dark);
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        body.dark-mode .sidebar h2 {
            color: var(--dark-text);
        }

        .sidebar a {
            display: block;
            color: var(--text);
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s ease, transform 0.3s ease;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .sidebar a:hover, .sidebar a.active {
            background: var(--primary);
            color: #ffffff;
            transform: translateX(3px);
        }

        .sidebar a i {
            font-size: 1.2rem;
            color: var(--accent);
        }

        /* Header */
        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
            position: fixed;
            width: calc(100% - 250px);
            top: 0;
            left: 250px;
            z-index: 1000;
            transition: background 0.3s ease;
        }

        .header h1 {
            font-size: 24px;
            color: var(--primary-dark);
        }

        body.dark-mode .header h1 {
            color: var(--dark-text);
        }

        .user-info {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            transition: transform 0.3s ease;
        }

        .user-info img:hover {
            transform: scale(1.1);
        }

        .user-info span {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        body.dark-mode .user-info span {
            color: var(--accent);
        }

        .profile-dropdown {
            position: absolute;
            top: 50px;
            right: 1rem;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 180px;
            display: none;
            z-index: 1002;
        }

        body.dark-mode .profile-dropdown {
            background: var(--dark-glass-bg);
            border-color: var(--dark-border);
        }

        .profile-dropdown.active {
            display: block;
        }

        .profile-dropdown a, .profile-dropdown button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.8rem;
            color: var(--text);
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 0.9rem;
        }

        body.dark-mode .profile-dropdown a, body.dark-mode .profile-dropdown button {
            color: var(--dark-text);
        }

        .profile-dropdown a:hover, .profile-dropdown button:hover {
            background: var(--primary);
            color: #ffffff;
        }

        .mode-indicator i {
            font-size: 1rem;
            color: var(--accent);
        }

        .sidebar-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            background: var(--primary-dark);
            border: 1px solid var(--border);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--accent);
            transition: transform 0.3s ease;
            z-index: 1002;
        }

        body.dark-mode .sidebar-toggle {
            border-color: var(--dark-border);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 80px 20px 20px;
            transition: margin-left 0.3s ease;
        }

        .metric-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            font-size: 14px;
            color: var(--primary-dark);
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        body.dark-mode .card h3 {
            color: var(--dark-text);
        }

        .card p {
            font-size: 22px;
            font-weight: bold;
            color: var(--text);
        }

        body.dark-mode .card p {
            color: var(--dark-text);
        }

        .card .subtext {
            font-size: 12px;
            color: var(--text-light);
        }

        body.dark-mode .card .subtext {
            color: var(--dark-text-light);
        }

        .charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
            border: 1px solid var(--border);
        }

        .chart-container:hover {
            transform: translateY(-3px);
        }

        .chart-container h3 {
            font-size: 16px;
            color: var(--primary-dark);
            margin-bottom: 15px;
            text-align: center;
        }

        body.dark-mode .chart-container h3 {
            color: var(--dark-text);
        }

        .date-filter-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        .date-filter-container label {
            color: var(--primary-dark);
            font-size: 14px;
        }

        body.dark-mode .date-filter-container label {
            color: var(--dark-text);
        }

        .date-filter-container input[type="date"] {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px;
            border-radius: 5px;
            font-size: 14px;
        }

        body.dark-mode .date-filter-container input[type="date"] {
            background: var(--dark-card-bg);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }

        .date-filter-container input[type="date"]:focus {
            outline: 2px solid var(--accent);
        }

        .date-filter-container button {
            background: var(--primary);
            border: none;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .date-filter-container button:hover {
            background: var(--primary-dark);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .charts {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-250px);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                width: 100%;
                left: 0;
            }

            .sidebar-toggle {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .metric-cards {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 600px) {
            .header h1 {
                font-size: 1.2rem;
            }

            .user-info img {
                width: 32px;
                height: 32px;
            }

            .user-info span {
                font-size: 0.85rem;
            }

            .profile-dropdown {
                width: 160px;
                top: 45px;
                right: 0.5rem;
            }

            .card {
                padding: 10px;
            }

            .card h3 {
                font-size: 12px;
            }

            .card p {
                font-size: 18px;
            }

            .date-filter-container {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Particle Background -->
    <div id="particles"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-toggle" id="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <h2>JIGJIGAHOMES</h2>
        <a href="tenant_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="browse_properties.php"><i class="fas fa-search"></i> Browse Properties</a>
        <a href="my_favorites.php"><i class="fas fa-heart"></i> My Favorites</a>
        <a href="my_leases.php"><i class="fas fa-file-signature"></i> My Leases</a>
        <a href="messages.php" class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-100">
    <i class="fas fa-envelope"></i> Messages
</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Header -->
    <div class="header" id="header">
        <h1>Tenant Dashboard</h1>
        <div class="user-info">
            <img src="<?= htmlspecialchars($tenant['profile_image'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($tenant['full_name']) . '&background=2a7f62&color=fff') ?>" alt="Profile">
            <span><?= htmlspecialchars($tenant['full_name']) ?></span>
            <div class="profile-dropdown" id="profile-dropdown">
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <button id="dark-mode-toggle"><i class="fas fa-moon mode-indicator"></i> <span>Dark Mode</span></button>
                <form method="POST" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="metric-cards">
            <div class="card">
                <h3>Total Favorites</h3>
                <p><?= $total_favorites ?></p>
                <p class="subtext">Saved Properties</p>
            </div>
            <div class="card">
                <h3>Active Leases</h3>
                <p><?= $active_leases ?></p>
                <p class="subtext">Current Rentals</p>
            </div>
            <div class="card">
                <h3>Total Payments</h3>
                <p>ETB <?= number_format($total_payments, 2) ?></p>
                <p class="subtext">Last 12 Months</p>
            </div>
            <div class="card">
                <h3>Pending Bookings</h3>
                <p><?= $pending_bookings ?></p>
                <p class="subtext">Awaiting Approval</p>
            </div>
            <div class="card">
                <h3>Recent Messages</h3>
                <p><?= $recent_messages ?></p>
                <p class="subtext">Last 30 Days</p>
            </div>
        </div>
        <div class="charts">
            <div class="chart-container">
                <h3>Lease Status Distribution</h3>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Payment Trend (Last 6 Months)</h3>
                <canvas id="paymentChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Favorite Property Types</h3>
                <canvas id="typeChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Property Locations</h3>
                <div id="map"></div>
            </div>
            <div class="chart-container">
                <h3>Booking Activity</h3>
                <div class="date-filter-container">
                    <label for="startDate">Start Date:</label>
                    <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                    <label for="endDate">End Date:</label>
                    <input type="date" id="endDate" value="<?= date('Y-m-d') ?>">
                    <button onclick="updateBookingChart()">Apply</button>
                </div>
                <canvas id="bookingChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        // Particle Background
        particlesJS('particles', {
            particles: {
                number: { value: 60, density: { enable: true, value_area: 800 } },
                color: { value: ['#2a7f62', '#f0c14b', '#1e3c2b'] },
                shape: { type: 'circle' },
                opacity: { value: 0.4, random: true },
                size: { value: 2, random: true },
                line_linked: { enable: false },
                move: { enable: true, speed: 0.8, direction: 'none', random: true }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' } },
                modes: { repulse: { distance: 80, duration: 0.4 }, push: { particles_nb: 3 } }
            },
            retina_detect: true
        });

        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const mainContent = document.getElementById('main-content');
        const header = document.getElementById('header');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
            header.classList.toggle('sidebar-active');
            sidebarToggle.innerHTML = sidebar.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Profile Dropdown
        const userInfo = document.querySelector('.user-info');
        const profileDropdown = document.getElementById('profile-dropdown');
        userInfo.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!userInfo.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const body = document.body;
        const modeIndicator = darkModeToggle.querySelector('.mode-indicator');
        const modeText = darkModeToggle.querySelector('span');
        const isDarkMode = localStorage.getItem('dark-mode') === 'enabled';

        if (isDarkMode) {
            body.classList.add('dark-mode');
            modeIndicator.className = 'fas fa-sun mode-indicator';
            modeText.textContent = 'Light Mode';
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isNowDark = body.classList.contains('dark-mode');
            localStorage.setItem('dark-mode', isNowDark ? 'enabled' : 'disabled');
            modeIndicator.className = isNowDark ? 'fas fa-sun mode-indicator' : 'fas fa-moon mode-indicator';
            modeText.textContent = isNowDark ? 'Light Mode' : 'Dark Mode';
        });

        // Lease Status Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Expired', 'Pending'],
                datasets: [{
                    data: [<?= $status_data['Active'] ?>, <?= $status_data['Expired'] ?>, <?= $status_data['Pending'] ?>],
                    backgroundColor: ['#2a7f62', '#f0c14b', '#1e3c2b'],
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
                            color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333',
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1e3c2b',
                        bodyColor: '#1e3c2b',
                        borderColor: '#f0c14b',
                        borderWidth: 1
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });

        // Payment Trend Area Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Payments (ETB)',
                    data: <?= json_encode($payment_data) ?>,
                    borderColor: '#f0c14b',
                    backgroundColor: 'rgba(240, 193, 75, 0.3)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#f0c14b',
                    pointHoverBackgroundColor: '#2a7f62'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333',
                            callback: function(value) {
                                return 'ETB ' + value;
                            }
                        },
                        grid: {
                            color: body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333'
                        },
                        grid: {
                            color: body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1e3c2b',
                        bodyColor: '#1e3c2b',
                        callbacks: {
                            label: function(context) {
                                return `ETB ${context.parsed.y.toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });

        // Favorite Property Types Bar Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($type_labels) ?>,
                datasets: [{
                    label: 'Favorite Count',
                    data: <?= json_encode($type_data) ?>,
                    backgroundColor: 'rgba(42, 127, 98, 0.7)',
                    borderColor: '#2a7f62',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333'
                        },
                        grid: {
                            color: body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333',
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
                            color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1e3c2b',
                        bodyColor: '#1e3c2b'
                    }
                }
            }
        });

        // Property Locations Map Placeholder
        const map = document.getElementById('map');
        map.style.height = '200px';
        map.style.background = body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        map.style.display = 'flex';
        map.style.alignItems = 'center';
        map.style.justifyContent = 'center';
        map.style.color = '#f0c14b';
        map.innerText = 'Map not available (requires coordinates)';

        // Booking Activity Bar Chart with Date Filter
        let bookingChart;
        const bookingCtx = document.getElementById('bookingChart').getContext('2d');
        const rawBookingData = <?= json_encode($booking_raw_data) ?>;
        const allLabels = <?= json_encode($booking_labels) ?>;
        const allData = <?= json_encode($booking_data) ?>;

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
                        backgroundColor: '#f0c14b',
                        borderColor: '#ffffff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333' },
                            grid: { color: body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' }
                        },
                        x: {
                            ticks: { color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333', maxRotation: 45, minRotation: 45 },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: body.classList.contains('dark-mode') ? '#e4e4e7' : '#333333' } },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#1e3c2b',
                            bodyColor: '#1e3c2b'
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
<?php $conn->close(); ?>