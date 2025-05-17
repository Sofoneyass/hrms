<?php
// Start the session
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Include the database connection file
require_once 'db_connection.php';
require_once 'header.php';
require_once 'hero_section.php';
require_once 'chooseus.php';
require_once 'featured.php';
require_once 'cta.php';

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


<!-- Hero Section -->


<!-- Why Choose Us -->


<!-- Featured Properties -->


<!-- Testimonials -->
<section class="testimonials section">
    <div class="container">
        <h2 class="section-title">Tenant Testimonials</h2>
        <div class="testimonial-cards">
            <div class="testimonial">
                <p>"Smooth process, great property, amazing support. I found my dream home!"</p>
                <h4>- Selam A.</h4>
            </div>
            <div class="testimonial">
                <p>"Affordable pricing and premium quality homes. Thank you HouseRental!"</p>
                <h4>- Dawit K.</h4>
            </div>
        </div>
    </div>
</section>


<?php
require_once 'footer.php';
?>
</body>
</html>


<?php
// Close the database connection
mysqli_close($conn);
?>
