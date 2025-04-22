<?php include 'auth_session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Owner Dashboard</title>
    <link rel="stylesheet" href="owner_dashboard.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="profile">
                <img src="uploads/<?php echo $_SESSION['profile_image']; ?>" alt="Profile">
                <h3><?php echo $_SESSION['full_name']; ?></h3>
                <p>Owner</p>
            </div>
            <nav>
                <ul>
                    <li><a href="owner_dashboard.php">Dashboard</a></li>
                    <li><a href="add_property.php">Add Property</a></li>
                    <li><a href="my_properties.php">My Properties</a></li>
                    <li><a href="owner_leases.php">Lease Agreements</a></li>
                    <li><a href="owner_payments.php">Rent Payments</a></li>
                    <li><a href="owner_income.php">Income Reports</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="owner_messages.php">Messages</a></li>
                    <li><a href="owner_maintenance.php">Maintenance Requests</a></li>
                    <li><a href="owner_legal.php">Legal Docs</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <h1>Welcome, <?php echo $_SESSION['full_name']; ?> 👋</h1>
            <p>Manage your properties, track income, respond to tenant requests, and more.</p>

            <section class="dashboard-widgets">
                <div class="widget">
                    <h3>Properties</h3>
                    <p><a href="my_properties.php">View & Manage</a></p>
                </div>
                <div class="widget">
                    <h3>Leases</h3>
                    <p><a href="owner_leases.php">View Agreements</a></p>
                </div>
                <div class="widget">
                    <h3>Payments</h3>
                    <p><a href="owner_payments.php">Track Rent</a></p>
                </div>
                <div class="widget">
                    <h3>Maintenance</h3>
                    <p><a href="owner_maintenance.php">View Requests</a></p>
                </div>
                <div class="widget">
                    <h3>Messages</h3>
                    <p><a href="owner_messages.php">Chat with Tenants/Admin</a></p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
