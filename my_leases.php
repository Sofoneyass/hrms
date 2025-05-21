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

// Fetch tenant's leases with property and booking information
$query = "SELECT l.*, p.title, p.address_detail, pp.photo_url, b.booking_id, b.status AS booking_status
          FROM leases l
          JOIN properties p ON l.property_id = p.property_id
          LEFT JOIN property_photos pp ON p.property_id = pp.property_id
          LEFT JOIN bookings b ON l.property_id = b.property_id AND b.tenant_id = ? AND b.status = 'pending'
          WHERE l.tenant_id = ?
          ORDER BY l.start_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $tenant_id, $tenant_id);
$stmt->execute();
$leases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle success/error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leases - JIGJIGAHOMES</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        body.dark-mode .card, body.dark-mode .message {
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

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
        }

        .success {
            background: rgba(76, 175, 80, 0.3);
            color: #28a745;
        }

        .error {
            background: rgba(244, 67, 54, 0.3);
            color: #dc3545;
        }

        .leases-table {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            color: var(--primary-dark);
            font-weight: bold;
            font-size: 0.95rem;
        }

        body.dark-mode th {
            color: var(--dark-text);
        }

        td img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #ffffff;
            margin-right: 5px;
            font-size: 0.9rem;
        }

        .view-btn {
            background: var(--primary);
        }

        .approve-btn {
            background: #28a745;
        }

        .reject-btn {
            background: #dc3545;
        }

        .no-leases {
            text-align: center;
            color: var(--text-light);
            font-size: 1rem;
            padding: 20px;
        }

        body.dark-mode .no-leases {
            color: var(--dark-text-light);
        }

        /* Responsive Design */
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

            table {
                font-size: 0.9rem;
            }

            .action-btn {
                padding: 6px 10px;
                font-size: 0.85rem;
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

            table {
                font-size: 0.8rem;
            }

            .action-btn {
                padding: 5px 8px;
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
        <a href="tenant_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="browse_properties.php"><i class="fas fa-search"></i> Browse Properties</a>
        <a href="my_favorites.php"><i class="fas fa-heart"></i> My Favorites</a>
        <a href="my_leases.php" class="active"><i class="fas fa-file-signature"></i> My Leases</a>
        <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Header -->
    <div class="header" id="header">
        <h1>My Leases</h1>
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
        <?php if ($success_message): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <div class="leases-table">
            <h3>My Leases</h3>
            <?php if (empty($leases)): ?>
                <p class="no-leases">You have no leases. Browse properties to start renting!</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Property Image</th>
                            <th>Property Title</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leases as $lease): ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($lease['photo_url'] ?? 'Uploads/default.jpg') ?>" alt="Property Image"></td>
                                <td><?= htmlspecialchars($lease['title']) ?></td>
                                <td><?= htmlspecialchars($lease['address_detail']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($lease['status'])) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($lease['start_date']))) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($lease['end_date']))) ?></td>
                                <td>
                                    <?php
                                    // Fetch latest payment status for the lease
                                    $payment_query = "SELECT payment_status FROM payments WHERE lease_id = ? ORDER BY payment_date DESC LIMIT 1";
                                    $payment_stmt = $conn->prepare($payment_query);
                                    $payment_stmt->bind_param("s", $lease['lease_id']);
                                    $payment_stmt->execute();
                                    $payment_result = $payment_stmt->get_result();
                                    $payment_status = $payment_result->num_rows > 0 ? $payment_result->fetch_assoc()['payment_status'] : 'No payments';
                                    $payment_stmt->close();
                                    echo htmlspecialchars(ucfirst($payment_status));
                                    ?>
                                </td>
                                <td>
                                    <a href="lease_detail.php?id=<?= htmlspecialchars($lease['lease_id']) ?>" class="action-btn view-btn">View Details</a>
                                    <?php if ($lease['booking_status'] === 'pending'): ?>
                                        <form action="manage_booking_status.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($lease['booking_id']) ?>">
                                            <input type="hidden" name="property_id" value="<?= htmlspecialchars($lease['property_id']) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="action-btn approve-btn">Approve</button>
                                        </form>
                                        <form action="manage_booking_status.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($lease['booking_id']) ?>">
                                            <input type="hidden" name="property_id" value="<?= htmlspecialchars($lease['property_id']) ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="action-btn reject-btn">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
    </script>
</body>
</html>
<?php $conn->close(); ?>