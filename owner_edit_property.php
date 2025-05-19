<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

$owner_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// Get property ID from query string
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $property_id = $_GET['id'];

    // Fetch property details
    $query = "SELECT * FROM properties WHERE property_id = ? AND owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $property_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $property = $result->fetch_assoc();
    $stmt->close();

    if (!$property) {
        $_SESSION['error_message'] = "Property not found or you do not have permission to edit it.";
        header("Location: manage_properties.php");
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = $_POST['title'];
        $address = $_POST['address'];
        $description = $_POST['description'];
        $price_per_month = $_POST['price_per_month'];
        $property_type = $_POST['property_type'];
        $bedrooms = $_POST['bedrooms'];
        $bathrooms = $_POST['bathrooms'];
        $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
        $status = $_POST['status'];
        $image_path = $property['image'];

        // Validate inputs
        if (!is_numeric($price_per_month) || $price_per_month < 0) {
            $error_message = "Invalid price per month.";
        } elseif (!is_numeric($bedrooms) || !is_numeric($bathrooms) || $bedrooms < 0 || $bathrooms < 0) {
            $error_message = "Invalid bedrooms or bathrooms count.";
        } else {
            // Handle image upload
            if (!empty($_FILES["image"]["name"])) {
                $target_dir = "Uploads/";
                $unique_name = uniqid() . '.' . strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                $target_file = $target_dir . $unique_name;
                $uploadOk = 1;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Check if directory exists and is writable
                if (!is_dir($target_dir) || !is_writable($target_dir)) {
                    $error_message = "Upload directory is not writable.";
                    $uploadOk = 0;
                }

                // Check if image file is valid
                $check = getimagesize($_FILES["image"]["tmp_name"]);
                if ($check === false) {
                    $error_message .= " File is not an image.";
                    $uploadOk = 0;
                }

                // Check file size (500KB)
                if ($_FILES["image"]["size"] > 500000) {
                    $error_message .= " File is too large.";
                    $uploadOk = 0;
                }

                // Allow certain file formats
                $allowed_types = ["jpg", "jpeg", "png", "gif"];
                if (!in_array($imageFileType, $allowed_types)) {
                    $error_message .= " Only JPG, JPEG, PNG & GIF files are allowed.";
                    $uploadOk = 0;
                }

                // Check if file already exists
                if (file_exists($target_file)) {
                    $error_message .= " File already exists.";
                    $uploadOk WAS = 0;
                }

                if ($uploadOk) {
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_path = $target_file;
                    } else {
                        $error_message .= " Error uploading file.";
                        $uploadOk = 0;
                    }
                }
            }

            if (!$error_message) {
                // Update property
                $query = "UPDATE properties SET title = ?, address = ?, description = ?, price_per_month = ?,
                          property_type = ?, bedrooms = ?, bathrooms = ?, amenities = ?, status = ?, image = ?
                          WHERE property_id = ? AND owner_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssssssss", $title, $address, $description, $price_per_month, $property_type, $bedrooms, $bathrooms, $amenities, $status, $image_path, $property_id, $owner_id);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Property updated successfully!";
                    header("Location: manage_properties.php");
                    exit;
                } else {
                    $error_message = "Error updating property: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
} else {
    $_SESSION['error_message'] = "Invalid property ID.";
    header("Location: manage_properties.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - JIGJIGAHOMES</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a2a44 0%, #2a4066 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar h2 {
            color: #FFD700;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: #ffffff;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #FFD700;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            background: none;
            border: none;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 5px;
            min-width: 150px;
            z-index: 1;
        }

        .dropdown-content a {
            color: #ffffff;
            padding: 12px 16px;
            display: block;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background: rgba(255, 215, 0, 0.2);
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            color: #FFD700;
            margin-bottom: 5px;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        input[type="file"] {
            padding: 5px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            gap: 15px;
        }

        .submit-btn {
            background: #FFD700;
            color: #1a2a44;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .error { color: #FF6347; margin-bottom: 15px; }
        .success { color: #4CAF50; margin-bottom: 15px; }

        img.preview {
            max-width: 100px;
            margin-top: 10px;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { position: relative; }
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 24px;
                color: #FFD700;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>JIGJIGAHOMES</h2>
        <a href="owner_dashboard.php">Dashboard</a>
        <a href="manage_properties.php" class="active">Manage Properties</a>
        <a href="manage_leases.php">Manage Leases</a>
        <a href="view_payments.php">View Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
            <h1>Edit Property</h1>
            <div class="profile-dropdown">
                <button class="profile-btn">Profile</button>
                <div class="dropdown-content">
                    <a href="profile.php">Edit Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <div class="form-container">
            <?php if ($error_message): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($property['location']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Price per Month</label>
                    <input type="number" name="price_per_month" value="<?php echo htmlspecialchars($property['price_per_month']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Property Type</label>
                    <select name="property_type" required>
                        <option value="Apartment" <?php if ($property['title'] == 'Apartment') echo 'selected'; ?>>Apartment</option>
                        <option value="Apartment" <?php if ($property['title'] == 'Apartment') echo 'selected'; ?>>Apartment</option>
                        
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Available" <?php if ($property['status'] == 'Available') echo 'selected'; ?>>Available</option>
                            <option value="Rented" <?php if ($property['status'] == 'Rented') echo 'selected'; ?>>Rented</option>
                            <option value="Maintenance" <?php if ($property['status'] == 'Maintenance') echo 'selected'; ?>>Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="image" accept="image/*">
                        <?php if ($property['image']): ?>
                            <img src="<?php echo htmlspecialchars($property['image']); ?>" class="preview" alt="Current Image">
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="submit-btn">Update Property</button>
                </form>
            </div>
        </div>
        <script>
            function toggleSidebar() {
                document.querySelector('.sidebar').classList.toggle('active');
            }
        </script>
    </body>
</html>