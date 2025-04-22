<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #333;
            height: 100vh;
            padding-top: 30px;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 15px 20px;
            border-bottom: 1px solid #444;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }

        .sidebar ul li:hover {
            background-color: #444;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px;
            flex: 1;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-header img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid #4CAF50;
            object-fit: cover;
        }

        .profile-table, .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .profile-table td, .dashboard-table td, .dashboard-table th {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        .profile-table td:first-child {
            font-weight: bold;
            background-color: #f9f9f9;
            width: 200px;
        }

        .dashboard-table th {
            background-color: #4CAF50;
            color: white;
            text-align: left;
        }

        .edit-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #ff6347;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .edit-btn:hover {
            background-color: #e65235;
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
    <ul>
        <li><a href="index.php">🏠 Home</a></li>
        <li><a href="profile.php">👤 Profile</a></li>
        <?php if ($role === 'owner'): ?>
            <li><a href="add_property.php">➕ Add Property</a></li>
            <li><a href="my_properties.php">🏠 My Properties</a></li>
            <li><a href="owner_dashboard.php"> Dashboard</a></li>
        <?php elseif ($role === 'tenant'): ?>
            <li><a href="reserved_properties.php">📄 My Reservations</a></li>
            <li><a href="tenant_dashboard.php"> Dashboard</a></li>
        <?php elseif ($role === 'admin'): ?>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="admin_dashboard.php"> Dashboard</a></li>
        <?php endif; ?>
        <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="profile-header">
        <img src="<?= $user['profile_image'] ?: 'uploads/default_user.png' ?>" alt="Profile Picture">
        <div>
            <h2><?= htmlspecialchars($user['full_name']) ?></h2>
            <a class="edit-btn" href="edit_profile.php">Edit Profile</a>
        </div>
    </div>

    <table class="profile-table">
        <tr>
            <td>Email</td>
            <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
        <tr>
            <td>Phone</td>
            <td><?= htmlspecialchars($user['phone']) ?></td>
        </tr>
        <tr>
            <td>Role</td>
            <td><?= ucfirst($user['role']) ?></td>
        </tr>
        <tr>
            <td>Joined On</td>
            <td><?= date('F j, Y', strtotime($user['created_at'])) ?></td>
        </tr>
    </table>

    <h3><?= ucfirst($role) ?> Dashboard Overview</h3>
    <table class="dashboard-table">
    <thead>
        <tr>
            <th>Feature</th>
            <th>Details</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($role === 'admin'): ?>
        <?php
            $userCount = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
            $propertyCount = $conn->query("SELECT COUNT(*) as total FROM properties")->fetch_assoc()['total'];
            $bookingCount = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
        ?>
        <tr><td>Total Users</td><td><?= $userCount ?></td></tr>
        <tr><td>Total Properties</td><td><?= $propertyCount ?></td></tr>
        <tr><td>Total Bookings</td><td><?= $bookingCount ?></td></tr>

    <?php elseif ($role === 'owner'): ?>
        <?php
            $propertyCount = $conn->query("SELECT COUNT(*) as total FROM properties WHERE owner_id = $user_id")->fetch_assoc()['total'];
            $bookingCount = $conn->query("SELECT COUNT(*) as total FROM bookings b JOIN properties p ON b.property_id = p.property_id WHERE p.owner_id = $user_id")->fetch_assoc()['total'];
        ?>
        <tr><td>Your Properties Listed</td><td><?= $propertyCount ?></td></tr>
        <tr><td>Total Bookings Received</td><td><?= $bookingCount ?></td></tr>

    <?php elseif ($role === 'tenant'): ?>
        <?php
            $reservedCount = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE tenant_id = $user_id")->fetch_assoc()['total'];
            $paymentTotal = $conn->query("SELECT SUM(amount) as total FROM  WHERE tenant_id = $user_id")->fetch_assoc()['total'] ?? 0;
            $maintenanceCount = $conn->query("SELECT COUNT(*) as total FROM maintenance_requests WHERE tenant_id = $user_id")->fetch_assoc()['total'];
        ?>
        <tr><td>Properties Reserved</td><td><?= $reservedCount ?></td></tr>
        <tr><td>Total Payments Made</td><td>BIRR <?= number_format($paymentTotal, 2) ?></td></tr>
        <tr><td>Maintenance Requests</td><td><?= $maintenanceCount ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>

</div>

</body>
</html>
