<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

    // Fetch existing amenities
    $amenities_query = "SELECT name FROM amenities WHERE property_id = ?";
    $amenities_stmt = $conn->prepare($amenities_query);
    $amenities_stmt->bind_param("s", $property_id);
    $amenities_stmt->execute();
    $amenities_result = $amenities_stmt->get_result();
    $existing_amenities = [];
    while ($row = $amenities_result->fetch_assoc()) {
        $existing_amenities[] = $row['name'];
    }
    $amenities_stmt->close();

    if (!$property) {
        $_SESSION['error_message'] = "Property not found or you do not have permission to edit it.";
        header("Location: my_properties.php");
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error_message = "Invalid CSRF token.";
        } else {
            $title = trim($_POST['title']);
            $address_detail = trim($_POST['address_detail']);
            $kebele = trim($_POST['kebele']);
            $zone = trim($_POST['zone']);
            $description = trim($_POST['description']);
            $price_per_month = $_POST['price_per_month'];
            $property_type = $_POST['property_type'];
            $bedrooms = $_POST['bedrooms'];
            $bathrooms = $_POST['bathrooms'];
            $status = $_POST['status'];
            $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
            $image_path = $property['image_path'] ?? '';

            // Validate inputs
            if (empty($title) || empty($address_detail) || empty($description)) {
                $error_message = "Title, address, and description are required.";
            } elseif (!is_numeric($price_per_month) || $price_per_month < 0) {
                $error_message = "Invalid price per month.";
            } elseif (!is_numeric($bedrooms) || !is_numeric($bathrooms) || $bedrooms < 0 || $bathrooms < 0) {
                $error_message = "Invalid bedrooms or bathrooms count.";
            } else {
                // Handle image upload
                if (!empty($_FILES["image"]["name"])) {
                    $target_dir = "uploads/";
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
                        $uploadOk = 0;
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
                    // Start transaction
                    $conn->begin_transaction();
                    try {
                        // Update property
                        $query = "UPDATE properties SET title = ?, address_detail = ?, kebele = ?, zone = ?, description = ?, price_per_month = ?, bedrooms = ?, bathrooms = ?, status = ? WHERE property_id = ? AND owner_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssssdiisss", $title, $address_detail, $kebele, $zone, $description, $price_per_month, $bedrooms, $bathrooms, $status, $property_id, $owner_id);
                        $stmt->execute();
                        $stmt->close();

                        // Update amenities (delete existing and insert new)
                        $delete_query = "DELETE FROM amenities WHERE property_id = ?";
                        $delete_stmt = $conn->prepare($delete_query);
                        $delete_stmt->bind_param("s", $property_id);
                        $delete_stmt->execute();
                        $delete_stmt->close();

                        foreach ($amenities as $amenity) {
                            $amenity_query = "INSERT INTO amenities (property_id, name) VALUES (?, ?)";
                            $amenity_stmt = $conn->prepare($amenity_query);
                            $amenity_stmt->bind_param("ss", $property_id, $amenity);
                            $amenity_stmt->execute();
                            $amenity_stmt->close();
                        }

                        // Update property_photos if image was uploaded
                        if ($image_path) {
                            $photo_query = "INSERT INTO property_photos (property_id, photo_url) VALUES (?, ?) ON DUPLICATE KEY UPDATE photo_url = ?";
                            $photo_stmt = $conn->prepare($photo_query);
                            $photo_stmt->bind_param("sss", $property_id, $image_path, $image_path);
                            $photo_stmt->execute();
                            $photo_stmt->close();
                        }

                        $conn->commit();
                        $_SESSION['success_message'] = "Property updated successfully!";
                        header("Location: my_properties.php");
                        exit;
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error_message = "Error updating property: " . $e->getMessage();
                    }
                }
            }
        }
    }
} else {
    $_SESSION['error_message'] = "Invalid property ID.";
    header("Location: my_properties.php");
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
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 16px;
        }

        input:focus, textarea:focus, select:focus {
            outline: 2px solid #FFD700;
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
            flex-wrap: wrap;
            gap: 15px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ffffff;
            font-weight: normal;
        }

        .submit-btn {
            background: #FFD700;
            color: #1a2a44;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #e6c200;
        }

        .error {
            color: #FF6347;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .success {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 14px;
        }

        img.preview {
            max-width: 100px;
            margin-top: 10px;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .header {
                position: relative;
            }
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
        <a href="my_properties.php" class="active">Manage Properties</a>
        <a href="owner_manage_leases.php">Manage Leases</a>
        <a href="messages.php">Messages</a>
        <a href="owner_view_payments.php">View Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
            <h1>Edit Property</h1>
            <div class="profile-dropdown">
                <button class="profile-btn" aria-label="Profile menu">Profile</button>
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required aria-required="true">
                </div>
                <div class="form-group">
                    <label for="address_detail">Address Detail</label>
                    <input type="text" id="address_detail" name="address_detail" value="<?php echo htmlspecialchars($property['address_detail']); ?>" required aria-required="true">
                </div>
                <div class="form-group">
                    <label for="kebele">Kebele</label>
                    <input type="text" id="kebele" name="kebele" value="<?php echo htmlspecialchars($property['kebele']); ?>">
                </div>
                <div class="form-group">
                    <label for="zone">Zone</label>
                    <input type="text" id="zone" name="zone" value="<?php echo htmlspecialchars($property['zone']); ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required aria-required="true"><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price_per_month">Price per Month (ETB)</label>
                    <input type="number" id="price_per_month" name="price_per_month" value="<?php echo htmlspecialchars($property['price_per_month']); ?>" required aria-required="true" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="property_type">Property Type</label>
                    <select id="property_type" name="property_type" required aria-required="true">
                        <option value="Apartment" <?php if ($property['title'] == 'Apartment') echo 'selected'; ?>>Apartment</option>
                        <option value="House" <?php if ($property['title'] == 'House') echo 'selected'; ?>>House</option>
                        <option value="Condo" <?php if ($property['title'] == 'Condo') echo 'selected'; ?>>Condo</option>
                        <option value="Villa" <?php if ($property['title'] == 'Villa') echo 'selected'; ?>>Villa</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bedrooms">Bedrooms</label>
                    <input type="number" id="bedrooms" name="bedrooms" value="<?php echo htmlspecialchars($property['bedrooms']); ?>" required aria-required="true" min="0">
                </div>
                <div class="form-group">
                    <label for="bathrooms">Bathrooms</label>
                    <input type="number" id="bathrooms" name="bathrooms" value="<?php echo htmlspecialchars($property['bathrooms']); ?>" required aria-required="true" min="0">
                </div>
                <div class="form-group">
                    <label>Amenities</label>
                    <div class="checkbox-group">
                        <?php
                        $available_amenities = ['WiFi', 'Parking', 'Pool', 'Gym', 'Security', 'Garden'];
                        foreach ($available_amenities as $amenity) {
                            $checked = in_array($amenity, $existing_amenities) ? 'checked' : '';
                            echo "<label><input type='checkbox' name='amenities[]' value='$amenity' $checked> $amenity</label>";
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required aria-required="true">
                        <option value="available" <?php if ($property['status'] == 'available') echo 'selected'; ?>>Available</option>
                        <option value="reserved" <?php if ($property['status'] == 'reserved') echo 'selected'; ?>>reserved</option>
                        <option value="rented" <?php if ($property['status'] == 'rented') echo 'selected'; ?>>Rented</option>
                        <option value="under_maintenance" <?php if ($property['status'] == 'under_maintenance') echo 'selected'; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="image">Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if (!empty($property['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($property['image_path']); ?>" class="preview" alt="Current Property Image">
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