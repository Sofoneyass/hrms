<?php
// Start the session to check user role
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Rental Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php" class="navbar-logo">House Rental</a>
        <ul class="navbar-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="available_properties.php">Available Properties</a></li>
            <li><a href="reserved_properties.php">Reserved Properties</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="messages.php">Messages</a></li>
            <?php endif; ?>
            <li><a href="help.php">Help</a></li>
            <li><a href="contact.php">Contact</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            <?php endif; ?>
            <li><a href="other.php">Other</a></li>
        </ul>
    </div>
</nav>

</body>
</html>
