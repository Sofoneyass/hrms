<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated values from the form
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // Handle profile image upload
    $profile_image = $user['profile_image'];  // Default profile image

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $file_name = $_FILES['profile_image']['name'];
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = "profile_" . $user_id . "." . $file_extension;

        if (move_uploaded_file($file_tmp, "uploads/" . $new_file_name)) {
            $profile_image = "uploads/" . $new_file_name;
        }
    }

    // Update the user profile in the database
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_image = ? WHERE user_id = ?");
    $stmt->bind_param("ssssi", $full_name, $email, $phone, $profile_image, $user_id);
    $stmt->execute();

    // Redirect to the profile page after update
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>
       body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #333;
    height: 100vh;
    padding-top: 30px;
    position: fixed;
    left: 0;
    top: 0;
    color: white;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 22px;
}

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    padding: 15px 20px;
    border-bottom: 1px solid #444;
}

.sidebar ul li a {
    color: white;
    text-decoration: none;
    display: block;
}

.sidebar ul li:hover {
    background-color: #444;
}

/* Main Content */
.main-content {
    margin-left: 250px;
    padding: 40px;
    flex: 1;
    background-color: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h2 {
    font-size: 28px;
    margin-bottom: 20px;
}

/* Form Styling */
form {
    max-width: 600px;
    margin: 0 auto;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

form div {
    margin-bottom: 20px;
}

label {
    font-size: 16px;
    font-weight: bold;
    display: block;
    margin-bottom: 8px;
}

input[type="text"],
input[type="email"],
input[type="file"] {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}

button[type="submit"] {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button[type="submit"]:hover {
    background-color: #45a049;
}

/* Profile Image */
img {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #4CAF50;
    margin-bottom: 20px;
}

/* Edit Profile Button */
.edit-btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #ff6347;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
}

.edit-btn:hover {
    background-color: #e65235;
}

    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
    <ul>
        <li><a href="index.php">🏠 Home</a></li>
        <li><a href="profile.php">👤 Profile</a></li>
        <?php if ($role === 'owner'): ?>
            <li><a href="add_property.php">➕ Add Property</a></li>
            <li><a href="my_properties.php">🏠 My Properties</a></li>
            <li><a href="owner_dashboard.php"> Dashboard</a></li>
        <?php elseif ($role === 'tenant'): ?>
            <li><a href="reserved_properties.php">📄 My Reservations</a></li>
            <li><a href="tenant_dashboard.php"> Dashboard</a></li>
        <?php elseif ($role === 'admin'): ?>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="admin_dashboard.php"> Dashboard</a></li>
        <?php endif; ?>
        <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Edit Profile</h2>

    <!-- Edit Profile Form -->
    <form method="POST" enctype="multipart/form-data">
        <div class="profile-header">
            <img src="<?= $user['profile_image'] ?: 'uploads/default_user.png' ?>" alt="Profile Picture" width="140" height="140" style="border-radius: 50%; object-fit: cover;">
        </div>
        <div>
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>
        <div>
            <label for="profile_image">Profile Image:</label>
            <input type="file" id="profile_image" name="profile_image">
        </div>
        <div>
            <button type="submit">Save Changes</button>
        </div>
    </form>
</div>

</body>
</html>
