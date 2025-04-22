<?php
require_once 'db_connection.php';
session_start();

// Fetch all properties with their first photo (if any)
$sql = "SELECT p.*, 
        (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS photo 
        FROM properties p ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Properties</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        
/* Navbar */
.navbar {
    background-color: #333;
    padding: 10px 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
}

.navbar-logo {
    color: white;
    font-size: 24px;
    font-weight: bold;
    text-decoration: none;
}

.navbar-links {
    list-style: none;
    display: flex;
    gap: 20px;
}

.navbar-links li {
    display: inline-block;
}

.navbar-links a {
    color: white;
    text-decoration: none;
    font-size: 18px;
    padding: 8px 16px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.navbar-links a:hover {
    background-color: #ff6347;
    color: white;
}

.navbar-links a.active {
    background-color: #4CAF50;
    color: white;
}

        .properties-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .properties-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 30px;
            color: #333;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .property-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .property-card:hover {
            transform: translateY(-5px);
        }

        .property-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #ddd;
        }

        .property-content {
            padding: 15px;
        }

        .property-content h3 {
            margin: 0 0 10px;
            font-size: 22px;
            color: #333;
        }

        .property-content p {
            color: #666;
            margin: 5px 0;
        }

        .property-content .price {
            color: #4CAF50;
            font-weight: bold;
            margin-top: 10px;
        }

        .property-content .status {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .status.available { background-color: #4CAF50; }
        .status.reserved { background-color: #FFA500; }
        .status.rented { background-color: #FF6347; }
        .status.under_maintenance { background-color: #888; }
    </style>
</head>
<body>
<!-- Navbar -->

<nav class="navbar">
    <div class="navbar-container">
    <a href="index.php" class="navbar-logo">House Rental</a>
        <ul class="navbar-links">
           
       
            <li><a href="index.php" >Home</a></li>
            <li><a href="properties.php" class="active">Available Properties</a></li>
            <li><a href="reserved_properties.php">Reserved Properties</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="messages.php">Messages</a></li>
            <li><a href="help.php">Help</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="logout.php">Logout</a></li>
            
            
               
            
        </ul>
    </div>
    </nav>   
<div class="properties-container">
    <h1 class="properties-title">Available Properties</h1>

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

</body>
</html>
