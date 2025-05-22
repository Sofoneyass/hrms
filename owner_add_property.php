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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $kebele = trim($_POST['kebele']);
    $zone = trim($_POST['zone']);
    $address_detail = trim($_POST['address_detail']);
    $description = trim($_POST['description']);
    $price_per_month = $_POST['price_per_month'];
    $bedrooms = $_POST['bedrooms'];
    $bathrooms = $_POST['bathrooms'];
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
    $status = $_POST['status'];

    // Validate inputs
    if (empty($title) || empty($location) || empty($kebele)) {
        $error_message = "Title, location (wereda), and kebele are required.";
    } elseif (!is_numeric($price_per_month) || $price_per_month < 0) {
        $error_message = "Invalid price per month.";
    } elseif (!is_numeric($bedrooms) || !is_numeric($bathrooms) || $bedrooms < 0 || $bathrooms < 0) {
        $error_message = "Invalid bedrooms or bathrooms count.";
    } else {
        // Handle image upload
        $image_path = null;
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
                $uploadOk = 0;
            }

            if ($uploadOk && !move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $error_message .= " Error uploading file.";
                $uploadOk = 0;
            }

            if ($uploadOk) {
                $image_path = $target_file;
            }
        }

        if (!$error_message) {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert property
                $query = "INSERT INTO properties (property_id, owner_id, title, description, location, address_detail, 
                          bedrooms, bathrooms, price_per_month, status, kebele, zone) 
                          VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssiidssss", $owner_id, $title, $description, $location, $address_detail, 
                                 $bedrooms, $bathrooms, $price_per_month, $status, $kebele, $zone);
                $stmt->execute();
                $property_id = $conn->insert_id; // Note: UUID is used, so insert_id may not apply; fetch UUID instead
                $stmt->close();

                // Fetch generated property_id
                $query = "SELECT property_id FROM properties WHERE owner_id = ? AND title = ? AND created_at = (SELECT MAX(created_at) FROM properties WHERE owner_id = ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $owner_id, $title, $owner_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $property_id = $result->fetch_assoc()['property_id'];
                $stmt->close();

                // Insert amenities
                if (!empty($amenities)) {
                    $query = "INSERT INTO amenities (property_id, name) VALUES (?, ?)";
                    $stmt = $conn->prepare($query);
                    foreach ($amenities as $amenity) {
                        $stmt->bind_param("ss", $property_id, $amenity);
                        $stmt->execute();
                    }
                    $stmt->close();
                }

                // Insert photo
                if ($image_path) {
                    $query = "INSERT INTO property_photos (property_id, photo_url) VALUES (?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ss", $property_id, $image_path);
                    $stmt->execute();
                    $stmt->close();
                }

                // Commit transaction
                $conn->commit();
                $_SESSION['success_message'] = "Property added successfully!";
                header("Location: my_properties.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error adding property: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - JIGJIGAHOMES</title>
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
            flex-wrap: wrap;
        }

        .submit-btn {
            background: #FFD700;
            color: #1a2a44;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #e6c200;
        }

        .error {
            color: #FF6347;
            margin-bottom: 15px;
        }

        .success {
            color: #4CAF50;
            margin-bottom: 15px;
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
        <a href="owner_manage_properties.php" class="active">Manage Properties</a>
        <a href="manage_leases.php">Manage Leases</a>
        <a href="messages.php">Messages</a>
        <a href="view_payments.php">View Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
            <h1>Add Property</h1>
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
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Location (Wereda)</label>
                    <input type="text" name="location" required>
                </div>
                <div class="form-group">
                    <label>Kebele</label>
                    <input type="text" name="kebele" required>
                </div>
                <div class="form-group">
                    <label>Zone</label>
                    <input type="text" name="zone">
                </div>
                <div class="form-group">
                    <label>Address Details</label>
                    <input type="text" name="address_detail">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Price per Month (ETB)</label>
                    <input type="number" name="price_per_month" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Bedrooms</label>
                    <input type="number" name="bedrooms" min="0" required>
                </div>
                <div class="form-group">
                    <label>Bathrooms</label>
                    <input type="number" name="bathrooms" min="0" required>
                </div>
                <div class="form-group">
                    <label>Amenities</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="amenities[]" value="WiFi"> WiFi</label>
                        <label><input type="checkbox" name="amenities[]" value="Parking"> Parking</label>
                        <label><input type="checkbox" name="amenities[]" value="Pool"> Pool</label>
                        <label><input type="checkbox" name="amenities[]" value="Gym"> Gym</label>
                        <label><input type="checkbox" name="amenities[]" value="Security"> Security</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="available">Available</option>
                        <option value="reserved">Reserved</option>
                        <option value="rented">Rented</option>
                        <option value="under_maintenance">Under Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <button type="submit" class="submit-btn">Add Property</button>
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