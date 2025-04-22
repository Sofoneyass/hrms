<?php
session_start();
require 'db_connection.php';

// Only owners can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['user_id'];

// Fetch owner properties + photo
$sql = "
    SELECT p.*, 
           (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS photo
    FROM properties p
    WHERE p.owner_id = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Properties</title>
    <link rel="stylesheet" href="style.css">  <!-- External CSS if needed -->
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .properties-container { max-width: 1200px; margin: auto; }
        .properties-title { text-align: center; margin-bottom: 20px; font-size: 24px; font-weight: bold; color: #333; }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .property-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: 0.3s;
            padding: 10px;
            text-align: center;
        }

        .property-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .property-content {
            padding: 15px;
        }

        .property-content h3 { margin-top: 0; font-size: 20px; font-weight: 600; }

        .price {
            font-weight: bold;
            color: #28a745;
            font-size: 18px;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 10px;
        }

        .status.available { background: #d4edda; color: #155724; }
        .status.reserved { background: #fff3cd; color: #856404; }
        .status.occupied { background: #f8d7da; color: #721c24; }

        .view-button, .edit-button {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .view-button:hover, .edit-button:hover {
            background: #0056b3;
        }

        .edit-button {
            background: #ffc107;
        }

        .edit-button:hover {
            background: #e0a800;
        }
    </style>
</head>
<body>

<div class="properties-container">
    <h1 class="properties-title">My Properties</h1>

    <div class="properties-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="property-card">
                    <img src="<?= $row['photo'] ? htmlspecialchars($row['photo']) : 'uploads/default.png' ?>" alt="Property Image">
                    <div class="property-content">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['location']) ?> — <?= htmlspecialchars($row['address_detail']) ?></p>
                        <p><?= $row['bedrooms'] ?> Beds | <?= $row['bathrooms'] ?> Baths</p>
                        <p class="price">BIRR <?= number_format($row['price_per_month'], 2) ?> / month</p>
                        <span class="status <?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                        <div>
                            <a href="property_detail.php?id=<?= $row['property_id'] ?>" class="view-button">View Details</a>
                            <a href="edit_property.php?id=<?= $row['property_id'] ?>" class="edit-button">Edit</a>
                            <a href="reserve_properties.php?id=<?= $row['property_id'] ?>" class="view-button">Reserve</a>
                        </div>
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
