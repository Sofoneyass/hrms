<?php
session_start();
require_once 'db_connection.php';

// Fetch all properties with their photos
$query = "
    SELECT p.*, pp.photo_url
    FROM properties p
    LEFT JOIN property_photos pp ON p.property_id = pp.property_id
    ORDER BY p.created_at DESC
";
$result = $conn->query($query);

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Properties | JIGJIGAHOMES</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        :root {
            --primary: #2a7f62;
            --secondary: #f0c14b;
            --accent: #e2b33a;
            --dark: #1e3c2b;
            --text-light: #fff;
            --text-muted: rgba(255, 255, 255, 0.7);
            --card-bg: rgba(255, 255, 255, 0.1);
            --card-border: rgba(255, 255, 255, 0.15);
            --glass-blur: blur(10px);
            --shadow-depth: 0 10px 30px rgba(0, 0, 0, 0.2);
            --hover-shadow: 0 5px 15px rgba(240, 193, 75, 0.3);
            --transition-fast: all 0.3s ease;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c2b 0%, #2a7f62 100%);
            color: var(--text-light);
            min-height: 100vh;
        }

        .main-content {
            margin-left: 270px;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--card-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--card-border);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-depth);
        }

        h1 {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--text-light);
            position: relative;
            padding-bottom: 10px;
        }

        h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 40px;
            height: 2px;
            background: var(--secondary);
        }

        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .property-card {
            background: var(--card-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-depth);
            transition: var(--transition-fast);
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .property-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .property-card h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 10px;
            color: var(--text-light);
        }

        .property-card p {
            margin: 5px 0;
            font-size: 15px;
            color: var(--text-muted);
        }

        .property-card .price {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary);
        }

        .property-card .status {
            font-weight: 500;
            color: #22c55e;
        }

        .no-properties {
            text-align: center;
            font-size: 16px;
            color: var(--text-muted);
        }

        .nav-link {
            text-align: center;
            margin-top: 20px;
        }

        .nav-link a {
            color: var(--secondary);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: var(--transition-fast);
        }

        .nav-link a:hover {
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 220px;
            }

            .property-grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    
    <div class="main-content" id="main-content">
        <div class="container">
            <h1>Properties</h1>
            <?php if ($result->num_rows > 0): ?>
                <div class="property-grid">
                    <?php while ($property = $result->fetch_assoc()): ?>
                        <div class="property-card">
                            <?php if (!empty($property['photo_url'])): ?>
                                <img src="<?php echo htmlspecialchars($property['photo_url']); ?>" alt="Property Image">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($property['location']); ?></p>
                            <p><strong>Zone:</strong> <?php echo htmlspecialchars($property['zone']); ?></p>
                            <p><strong>Kebele:</strong> <?php echo htmlspecialchars($property['kebele']); ?></p>
                            <?php if (!empty($property['description'])): ?>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($property['description']); ?></p>
                            <?php endif; ?>
                            <p><strong>Bedrooms:</strong> <?php echo $property['bedrooms']; ?></p>
                            <p><strong>Bathrooms:</strong> <?php echo $property['bathrooms']; ?></p>
                            <p class="price">$<?php echo number_format($property['price_per_month'], 2); ?>/month</p>
                            <p class="status"><?php echo htmlspecialchars($property['status']); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-properties">No properties found.</p>
            <?php endif; ?>
            <div class="nav-link">
                <a href="/add-property">Add New Property</a>
            </div>
        </div>
    </div>
</body>
</html>