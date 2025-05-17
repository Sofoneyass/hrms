<?php
session_start();
require_once 'db_connection.php';

// Only owners can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit();
}

// Function to generate UUID v4
function generate_uuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$success = $error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $owner_id = $_SESSION['user_id']; // UUID string
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $zone = isset($_POST['zone']) ? trim($_POST['zone']) : '';
    $kebele = isset($_POST['kebele']) ? trim($_POST['kebele']) : '';
    $address_detail = isset($_POST['address_detail']) ? trim($_POST['address_detail']) : '';
    $bedrooms = isset($_POST['bedrooms']) ? trim($_POST['bedrooms']) : '';
    $bathrooms = isset($_POST['bathrooms']) ? trim($_POST['bathrooms']) : '';
    $price_per_month = isset($_POST['price_per_month']) ? trim($_POST['price_per_month']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Debug: Log received POST data
    error_log("POST data: " . print_r($_POST, true));
    error_log("Owner ID: " . ($owner_id ?? 'Not set'));

    // Validation
    $errors = [];
    if (empty($title)) {
        $errors[] = "Field 'title' is missing or empty at line " . __LINE__;
    }
    if (empty($location)) {
        $errors[] = "Field 'location' is missing or empty at line " . __LINE__;
    }
    if (empty($zone)) {
        $errors[] = "Field 'zone' is missing or empty at line " . __LINE__;
    }
    if (empty($kebele)) {
        $errors[] = "Field 'kebele' is missing or empty at line " . __LINE__;
    }
    if ($bedrooms === '' || !is_numeric($bedrooms) || $bedrooms < 0) {
        $errors[] = "Field 'bedrooms' is missing, invalid, or negative at line " . __LINE__;
    }
    if ($bathrooms === '' || !is_numeric($bathrooms) || $bathrooms < 0) {
        $errors[] = "Field 'bathrooms' is missing, invalid, or negative at line " . __LINE__;
    }
    if ($price_per_month === '' || !is_numeric($price_per_month) || $price_per_month <= 0) {
        $errors[] = "Field 'price_per_month' is missing, invalid, or not positive at line " . __LINE__;
    }
    if (empty($status)) {
        $errors[] = "Field 'status' is missing or empty at line " . __LINE__;
    }
    if (empty($owner_id)) {
        $errors[] = "Owner ID is missing from session at line " . __LINE__;
    }

    if (!empty($errors)) {
        $error = implode("; ", $errors);
        error_log("Validation errors: " . $error);
    } else {
        // Generate UUID for property
        $property_id = generate_uuid();

        // Insert property details
        $stmt = $conn->prepare("INSERT INTO properties (id, owner_id, title, description, location, zone, kebele, address_detail, bedrooms, bathrooms, price_per_month, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error = "Database error: Unable to prepare statement at line " . __LINE__;
            error_log("Prepare error: " . $conn->error);
        } else {
            $stmt->bind_param("ssssssssiids", $property_id, $owner_id, $title, $description, $location, $zone, $kebele, $address_detail, $bedrooms, $bathrooms, $price_per_month, $status);

            if ($stmt->execute()) {
                // Handle image upload
                if (!empty($_FILES['property_image']['name'])) {
                    $targetDir = "Uploads/";
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    $fileName = basename($_FILES['property_image']['name']);
                    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $targetFilePath = $targetDir . time() . '_' . $fileName;

                    // Check file type
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($fileType, $allowedTypes)) {
                        if (move_uploaded_file($_FILES['property_image']['tmp_name'], $targetFilePath)) {
                            $photo_id = generate_uuid();
                            $stmt2 = $conn->prepare("INSERT INTO property_photos (id, property_id, photo_url) VALUES (?, ?, ?)");
                            if (!$stmt2) {
                                $error = "Database error: Unable to prepare image statement at line " . __LINE__;
                                error_log("Prepare image error: " . $conn->error);
                            } else {
                                $stmt2->bind_param("sss", $photo_id, $property_id, $targetFilePath);
                                $stmt2->execute();
                                $success = "Property added successfully with image!";
                                $stmt2->close();
                            }
                        } else {
                            $error = "Error uploading image at line " . __LINE__;
                        }
                    } else {
                        $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed at line " . __LINE__;
                    }
                } else {
                    $success = "Property added successfully (no image uploaded).";
                }
            } else {
                $error = "Error adding property: " . $stmt->error . " at line " . __LINE__;
                error_log("Execute error: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}

$conn->close();

// Redirect back to add_property.php with success/error message
$_SESSION['success'] = $success;
$_SESSION['error'] = $error;
header("Location: add_property.php");
exit();
?>