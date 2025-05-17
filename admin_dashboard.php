<?php
include 'auth_session.php';
require 'db_connection.php';

$adminId = $_SESSION['user_id'];

$adminQuery = $conn->query("SELECT full_name, email, phone, role, profile_image FROM users WHERE user_id = $adminId");
if (!$adminQuery) {
    die("Admin query failed: " . $conn->error);
}
$admin = $adminQuery->fetch_assoc();

function getCount($conn, $table) {
    $result = $conn->query("SELECT COUNT(*) as total FROM $table");
    if (!$result) {
        die("Count query failed on $table: " . $conn->error);
    }
    return $result->fetch_assoc()['total'];
}

$userCount = getCount($conn, 'users');
$propertyCount = getCount($conn, 'properties');
$bookingCount = getCount($conn, 'bookings');
$leaseCount = getCount($conn, 'leases');
$invoiceCount = getCount($conn, 'invoices');
$paymentCount = getCount($conn, 'payments');
$requestCount = getCount($conn, 'maintenance_requests');
$messageCount = getCount($conn, 'messages');
$notificationCount = getCount($conn, 'notifications');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-yXYr8GrH6BJwdrW0uOflpIhMD8w/BXD0A2EB6DOPz5pKCrptYGRSMZwGdS0KU4Xxch3J+T5vl6rFMcxI9jhV3g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            display: flex;
            min-height: 100vh;
            background: #f4f6f9;
        }
        .sidebar {
            width: 250px;
            background: #1e1e2f;
            color: #fff;
            padding: 30px 20px;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #00c3ff;
        }
        .sidebar a {
            display: block;
            color: #ddd;
            text-decoration: none;
            padding: 10px 0;
            margin-bottom: 10px;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background: #2c2c3e;
            color: #00c3ff;
            border-left: 4px solid #00c3ff;
            padding-left: 10px;
        }
        .main {
            flex: 1;
            padding: 40px;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        .header-info h1 {
            font-size: 28px;
            color: #333;
        }
        .header-info p {
            color: #777;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }
        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #555;
        }
        .card p {
            font-size: 22px;
            font-weight: bold;
            color: #00c3ff;
        }
        @media(max-width: 768px) {
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="#"><i class="fas fa-home"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="manage_properties.php"><i class="fas fa-building"></i> Manage Properties</a>
        <a href="site_analytics.php"><i class="fas fa-chart-line"></i> View Analytics</a>
        <a class="fas fa-chart-line" href="edit_profile.php">Edit Profile</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <div class="header">
            <img src="uploads/<?php echo htmlspecialchars($admin['profile_image']); ?>" alt="Admin Photo">
            <div class="header-info">
                <h1>Welcome, <?php echo htmlspecialchars($admin['full_name']); ?></h1>
                <p>Admin - overseeing all system operations</p>
            </div>
        </div>

        <div class="stats">
            <div class="card"><h3>Users</h3><p><?php echo $userCount; ?></p></div>
            <div class="card"><h3>Properties</h3><p><?php echo $propertyCount; ?></p></div>
            <div class="card"><h3>Bookings</h3><p><?php echo $bookingCount; ?></p></div>
            <div class="card"><h3>Leases</h3><p><?php echo $leaseCount; ?></p></div>
            <div class="card"><h3>Invoices</h3><p><?php echo $invoiceCount; ?></p></div>
            <div class="card"><h3>Payments</h3><p><?php echo $paymentCount; ?></p></div>
            <div class="card"><h3>Maintenance</h3><p><?php echo $requestCount; ?></p></div>
            <div class="card"><h3>Messages</h3><p><?php echo $messageCount; ?></p></div>
            <div class="card"><h3>Notifications</h3><p><?php echo $notificationCount; ?></p></div>
        </div>
    </div>

</body>
</html>