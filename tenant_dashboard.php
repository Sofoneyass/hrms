<?php include 'auth_session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenant Dashboard</title>
    <link rel="stylesheet" href="tenant_dashboard.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="profile">
                <img src="uploads/<?php echo $_SESSION['profile_image']; ?>" alt="Profile">
                <h3><?php echo $_SESSION['full_name']; ?></h3>
                <p>Tenant</p>
            </div>
            <nav>
                <ul>
                    <li><a href="tenant_dashboard.php">Dashboard</a></li>
                    <li><a href="search_properties.php">Search Properties</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="tenant_leases.php">Lease Info</a></li>
                    <li><a href="tenant_payments.php">Payments</a></li>
                    <li><a href="tenant_maintenance.php">Maintenance</a></li>
                    <li><a href="tenant_documents.php">Documents</a></li>
                    <li><a href="tenant_reviews.php">Reviews & Ratings</a></li>
                    <li><a href="tenant_messages.php">Messages</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <h1>Welcome <?php echo $_SESSION['full_name']; ?> 👋</h1>
            <p>You can manage your rentals, payments, maintenance requests, and more from here.</p>

            <section class="dashboard-widgets">
                <div class="widget"><h3>🏠 Search</h3><p><a href="search_properties.php">Find your next home</a></p></div>
                <div class="widget"><h3>📄 Lease Info</h3><p><a href="tenant_leases.php">View your leases</a></p></div>
                <div class="widget"><h3>💳 Payments</h3><p><a href="tenant_payments.php">Manage payments</a></p></div>
                <div class="widget"><h3>🔧 Maintenance</h3><p><a href="tenant_maintenance.php">Request repairs</a></p></div>
                <div class="widget"><h3>📁 Documents</h3><p><a href="tenant_documents.php">Access stored files</a></p></div>
                <div class="widget"><h3>💬 Messages</h3><p><a href="tenant_messages.php">Chat with landlord/admin</a></p></div>
                <div class="widget"><h3>📝 Reviews</h3><p><a href="tenant_reviews.php">Rate your experience</a></p></div>
            </section>
        </main>
    </div>
</body>
</html>