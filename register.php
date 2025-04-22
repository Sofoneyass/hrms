<?php
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    // Handle profile image upload
    $profileImage = $_FILES['profile_image']['name'];
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($profileImage);
    move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile);

    $sql = "INSERT INTO users (full_name, email, password, phone, role, profile_image) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $email, $password, $phone, $role, $targetFile);

    if ($stmt->execute()) {
        echo "Registration successful. ";
        header("Location: login.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    
}
?>

<<div class="auth-container">
<link rel="stylesheet" href="style.css">
    <h2>Register</h2>
    <form method="post" enctype="multipart/form-data">
        <!-- your form inputs -->
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="phone" placeholder="Phone">
        <select name="role">
            <option value="tenant">Tenant</option>
            <option value="owner">Owner</option>
            <option value="admin">Admin</option>
        </select>
        <input type="file" name="profile_image" accept="image/*" required>
        <button type="submit">Register</button>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </form>
</div>

