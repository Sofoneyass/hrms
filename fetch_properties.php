<?php
include 'db_connection.php'; // assumes $conn is your MySQLi connection object

$where = [];
$params = [];
$types = "";

// Collect filter criteria
if (!empty($_POST['location'])) {
    $where[] = "p.location LIKE ?";
    $params[] = "%" . $_POST['location'] . "%";
    $types .= "s";
}
if (!empty($_POST['min_price'])) {
    $where[] = "p.price_per_month >= ?";
    $params[] = $_POST['min_price'];
    $types .= "d";
}
if (!empty($_POST['max_price'])) {
    $where[] = "p.price_per_month <= ?";
    $params[] = $_POST['max_price'];
    $types .= "d";
}
if (!empty($_POST['bedrooms'])) {
    $where[] = "p.bedrooms >= ?";
    $params[] = $_POST['bedrooms'];
    $types .= "i";
}
if (!empty($_POST['status'])) {
    $where[] = "p.status = ?";
    $params[] = $_POST['status'];
    $types .= "s";
}

// Build query
$sql = "
SELECT 
    p.*, 
    (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS main_photo
FROM properties p
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($prop = $result->fetch_assoc()) {
        $photo = $prop['main_photo'] ?? 'default.jpg'; // fallback image

        echo "<div class='property'>
                <img src='uploads/{$photo}' alt='Property Photo' height='150'>
                <h3>" . htmlspecialchars($prop['title']) . "</h3>
                <p><strong>Location:</strong> " . htmlspecialchars($prop['location']) . "</p>
                <p><strong>Address:</strong> " . htmlspecialchars($prop['address_detail']) . "</p>
                <p><strong>Price:</strong> \$" . number_format($prop['price_per_month'], 2) . " / month</p>
                <p><strong>Bedrooms:</strong> {$prop['bedrooms']} | <strong>Bathrooms:</strong> {$prop['bathrooms']}</p>
                <p><strong>Status:</strong> " . ucfirst($prop['status']) . "</p>
                <a href='property_details.php?id={$prop['property_id']}'>View Details</a>
              </div>";
    }
} else {
    echo "<p>No properties found matching your criteria.</p>";
}
?>
