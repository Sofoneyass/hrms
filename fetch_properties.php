<?php
include 'db_connection.php'; 
header('Content-Type: application/json');

session_start();
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Initialize base SQL
$where = [];
$params = [];
$types = "";

// Start SQL with user_id first because it's used first
$sql = "
    SELECT 
        p.*, 
        (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS main_photo,
        (SELECT COUNT(*) FROM favorites f WHERE f.property_id = p.property_id AND f.tenant_id = ?) AS is_favorited
    FROM properties p
";

// Add user_id for first ? in query
$params[] = $user_id;
$types .= "i";

// Collect filters
if (!empty($_GET['location'])) {
    $where[] = "p.location LIKE ?";
    $params[] = "%" . filter_var($_GET['location'], FILTER_SANITIZE_STRING) . "%";
    $types .= "s";
}
if (!empty($_GET['title'])) {
    $where[] = "p.title LIKE ?";
    $params[] = "%" . filter_var($_GET['title'], FILTER_SANITIZE_STRING) . "%";
    $types .= "s";
}
if (!empty($_GET['min_price'])) {
    $where[] = "p.price_per_month >= ?";
    $params[] = floatval($_GET['min_price']);
    $types .= "d";
}
if (!empty($_GET['max_price'])) {
    $where[] = "p.price_per_month <= ?";
    $params[] = floatval($_GET['max_price']);
    $types .= "d";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// Debugging (optional)
error_log("SQL: " . $sql);
error_log("Params: " . json_encode($params));
error_log("Types: " . $types);

// Bind parameters
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$data = [];
while ($prop = $result->fetch_assoc()) {
    $data[] = [
        'title' => $prop['title'],
        'location' => $prop['location'],
        'price' => $prop['price_per_month'],
        'bedrooms' => $prop['bedrooms'],
        'photo' => $prop['main_photo'],
        'status' => $prop['status'],
        'property_id' => $prop['property_id']
    ];
}

echo json_encode([
    "success" => true,
    "properties" => $data
]);

$stmt->close();
$conn->close();
?>
