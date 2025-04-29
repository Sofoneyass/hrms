<?php
session_start();
require_once 'db_connection.php';

// Only owners can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit();
}

$success = $error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $location = $_POST['zone'];
    $location = $_POST['kebele'];
    $address_detail = $_POST['address_detail'];
    $bedrooms = $_POST['bedrooms'];
    $bathrooms = $_POST['bathrooms'];
    $price_per_month = $_POST['price_per_month'];
    $status = $_POST['status'];

    // Insert property details
    $stmt = $conn->prepare("INSERT INTO properties (owner_id, title, description, location, address_detail, bedrooms, bathrooms, price_per_month, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiiis", $owner_id, $title, $description, $location, $address_detail, $bedrooms, $bathrooms, $price_per_month, $status);

    if ($stmt->execute()) {
        $property_id = $stmt->insert_id;

        // Handle image upload
        if (!empty($_FILES['property_image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = basename($_FILES['property_image']['name']);
            $targetFilePath = $targetDir . time() . '_' . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            // Check file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['property_image']['tmp_name'], $targetFilePath)) {
                    $stmt2 = $conn->prepare("INSERT INTO property_photos (property_id, photo_url) VALUES (?, ?)");
                    $stmt2->bind_param("is", $property_id, $targetFilePath);
                    $stmt2->execute();
                    $success = "Property added successfully with image!";
                } else {
                    $error = "Error uploading image.";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        } else {
            $success = "Property added successfully (no image uploaded).";
        }
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Property</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .form-container input, .form-container textarea, .form-container select {
            width: 100%;
            padding: 12px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .form-container button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            font-size: 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            font-size: 18px;
            margin-top: 20px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Add New Property</h2>

    <?php if (!empty($success)) echo "<div class='message success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='message error'>$error</div>"; ?>

    <form method="POST" enctype="multipart/form-data">
    <select name="title" required>
        <option value="">Select Property Title</option>
        <option value="condo">condo</option>
        <option value="apartment">apartment</option>
        <option value="villa">villa</option>
   
    </select>

        <textarea name="description" placeholder="Property Description" ></textarea>
        <input type="text" name="location" placeholder="City/Location" required>
        <input type="text" name="zone" placeholder="zone" required>
        <input type="text" name="kebele" placeholder="kebele" required>
        <input type="text" name="address_detail" placeholder="Detailed Address" >
        <input type="number" name="bedrooms" placeholder="Number of Bedrooms" min="0" required>
        <input type="number" name="bathrooms" placeholder="Number of Bathrooms" min="0" required>
        <input type="number" step="0.01" name="price_per_month" placeholder="Price Per Month" required>
        <select name="status" required>
            <option value="available">Available</option>
            <option value="reserved">Reserved</option>
            <option value="rented">Rented</option>
            <option value="under_maintenance">Under Maintenance</option>
        </select>
        <input type="file" name="property_image" accept="image/*">
        <button type="submit">Add Property</button>
    </form>
</div>

</body>
</html>
