<?php
// Start the session
session_start();

// Include the database connection file
require_once 'db_connection.php'; // Make sure you have a db_connection.php file that connects to your MySQL database

// Fetch properties listed by owners from the database
$sql = "SELECT p.*, 
               (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS photo 
        FROM properties p 
        WHERE p.status = 'available'
        ORDER BY p.created_at DESC 
        LIMIT 6";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - House Rental Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php" class="navbar-logo">House Rental</a>
        <ul class="navbar-links">
           
        <?php if (!isset($_SESSION['user_id'])): ?>
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="properties.php">Available Properties</a></li>
            <li><a href="reserved_properties.php">Reserved Properties</a></li>
            <li><a href="help.php">Help</a></li>
            <li><a href="contact.php">Contact</a></li> 
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Sign Up</a></li> 
         <?php endif; ?>
           
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="properties.php">Available Properties</a></li>
            <li><a href="reserved_properties.php">Reserved Properties</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="messages.php">Messages</a></li>
            <li><a href="help.php">Help</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="logout.php">Logout</a></li>
            
            
               
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Find Your Dream Home</h1>
        <p>Explore top rental properties and make your next move today.</p>
        <a href="available_properties.php" class="hero-button">Browse Properties</a>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="properties-container">
    <h2>Featured Properties</h2>
    <div class="properties-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="property-card">
                    <img src="<?= $row['photo'] ? $row['photo'] : 'uploads/default.png' ?>" alt="Property Image">
                    <div class="property-content">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['location']) ?> — <?= htmlspecialchars($row['address_detail']) ?></p>
                        <p><?= $row['bedrooms'] ?> Beds | <?= $row['bathrooms'] ?> Baths</p>
                        <p class="price">BIRR <?= number_format($row['price_per_month'], 2) ?> / month</p>
                        <span class="status <?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                        <a href="property_detail.php?id=<?= $row['property_id'] ?>" class="view-button">View Details</a>
                        <a href="reserve_property.php?id=<?= $row['property_id'] ?>" class="view-button">reserve</a>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No properties listed yet.</p>
        <?php endif; ?>
    </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta">
    <div class="cta-content">
        <h2>Ready to Rent?</h2>
        <p>Start your journey to finding the perfect home now.</p>
        <a href="available_properties.php" class="cta-button">Browse All Properties</a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <p>&copy; 2025 House Rental Management | All Rights Reserved</p>
        <ul class="footer-links">
            <li><a href="privacy.php">Privacy Policy</a></li>
            <li><a href="terms.php">Terms of Service</a></li>
        </ul>
    </div>
</footer>

</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
