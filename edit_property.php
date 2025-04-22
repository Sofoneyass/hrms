<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Property ID is required.");
}

$property_id = intval($_GET['id']);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = $_POST['description'];
    $location = $_POST['location'];
    $zone = $_POST['zone'];
    $kebele = $_POST['kebele'];
    $address_detail = $_POST['address_detail'];
    $bedrooms = intval($_POST['bedrooms']);
    $bathrooms = intval($_POST['bathrooms']);
    $price_per_month = floatval($_POST['price_per_month']);
    $status = $_POST['status'];

    // Update property details
    $update_sql = "
        UPDATE properties 
        SET description=?, location=?, zone=?, kebele=?, address_detail=?, 
            bedrooms=?, bathrooms=?, price_per_month=?, status=? 
        WHERE property_id=? AND owner_id=?
    ";

    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        die("Prepare failed on line " . __LINE__ . ": " . $conn->error);
    }

    $stmt->bind_param(
        "ssssssiidii",
        $description, $location, $zone, $kebele, $address_detail,
        $bedrooms, $bathrooms, $price_per_month, $status,
        $property_id, $owner_id
    );

    if (!$stmt->execute()) {
        die("Execute failed on line " . __LINE__ . ": " . $stmt->error);
    }

    // Handle image upload
    if (isset($_FILES['property_image']) && $_FILES['property_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        $image_name = basename($_FILES['property_image']['name']);
        $target_file = $upload_dir . time() . "_" . $image_name;

        if (move_uploaded_file($_FILES['property_image']['tmp_name'], $target_file)) {
            // Insert or update image
            $check_sql = "SELECT * FROM property_photos WHERE property_id=?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                die("Prepare failed on line " . __LINE__ . ": " . $conn->error);
            }

            $check_stmt->bind_param("i", $property_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                $update_photo_sql = "UPDATE property_photos SET photo_url=? WHERE property_id=?";
                $photo_stmt = $conn->prepare($update_photo_sql);
                if (!$photo_stmt) {
                    die("Prepare failed on line " . __LINE__ . ": " . $conn->error);
                }
                $photo_stmt->bind_param("si", $target_file, $property_id);
                $photo_stmt->execute();
            } else {
                $insert_photo_sql = "INSERT INTO property_photos (property_id, photo_url) VALUES (?, ?)";
                $photo_stmt = $conn->prepare($insert_photo_sql);
                if (!$photo_stmt) {
                    die("Prepare failed on line " . __LINE__ . ": " . $conn->error);
                }
                $photo_stmt->bind_param("is", $property_id, $target_file);
                $photo_stmt->execute();
            }
        }
    }

    header("Location: my_properties.php");
    exit();
}

// Fetch existing property info
$select_sql = "
    SELECT p.*, ph.photo_url 
    FROM properties p 
    LEFT JOIN property_photos ph ON p.property_id = ph.property_id 
    WHERE p.property_id=? AND p.owner_id=?
";

$stmt = $conn->prepare($select_sql);
if (!$stmt) {
    die("Prepare failed on line " . __LINE__ . ": " . $conn->error);
}

$stmt->bind_param("ii", $property_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    die("Property not found or unauthorized.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Property</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f9f9f9; }
        form { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        input, textarea, select { width: 100%; margin-bottom: 15px; padding: 10px; border-radius: 4px; border: 1px solid #ccc; }
        button { padding: 10px 15px; background: #28a745; color: white; border: none; border-radius: 4px; }
        img { max-width: 100%; height: auto; margin-bottom: 15px; }
    </style>
</head>
<body>

<h2>Edit Property</h2>

<form action="" method="POST" enctype="multipart/form-data">
    <textarea name="description" placeholder="Property Description" required><?= htmlspecialchars($property['description']) ?></textarea>
    <input type="text" name="location" placeholder="City/Location" value="<?= htmlspecialchars($property['location']) ?>" required>
    <input type="text" name="zone" placeholder="Zone" value="<?= htmlspecialchars($property['zone']) ?>" required>
    <input type="text" name="kebele" placeholder="Kebele" value="<?= htmlspecialchars($property['kebele']) ?>" required>
    <input type="text" name="address_detail" placeholder="Detailed Address" value="<?= htmlspecialchars($property['address_detail']) ?>">
    <input type="number" name="bedrooms" placeholder="Number of Bedrooms" min="0" value="<?= htmlspecialchars($property['bedrooms']) ?>" required>
    <input type="number" name="bathrooms" placeholder="Number of Bathrooms" min="0" value="<?= htmlspecialchars($property['bathrooms']) ?>" required>
    <input type="number" step="0.01" name="price_per_month" placeholder="Price Per Month" value="<?= htmlspecialchars($property['price_per_month']) ?>" required>

    <select name="status" required>
        <option value="available" <?= $property['status'] === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="reserved" <?= $property['status'] === 'reserved' ? 'selected' : '' ?>>Reserved</option>
        <option value="rented" <?= $property['status'] === 'rented' ? 'selected' : '' ?>>Rented</option>
        <option value="under_maintenance" <?= $property['status'] === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
    </select>

    <?php if ($property['photo_url']): ?>
        <label>Current Image:</label>
        <img src="<?= htmlspecialchars($property['photo_url']) ?>" alt="Current Image">
    <?php endif; ?>
    
    <label>Upload New Image (optional):</label>
    <input type="file" name="property_image" accept="image/*">

    <button type="submit">Update Property</button>
</form>

</body>
</html>
