<?php
$pageTitle = "Add Property";
require_once 'db_connection.php';
require_once 'admin_header.php';


$errors = [];
$successMessage = '';
$title = $description = $location = $address_detail = $owner_id = $bedrooms = $bathrooms = $price_per_month = $status = $kebele = $zone = '';
$amenities = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');
    $owner_id = trim($_POST['owner_id'] ?? '');
    $bedrooms = trim($_POST['bedrooms'] ?? '');
    $bathrooms = trim($_POST['bathrooms'] ?? '');
    $price_per_month = trim($_POST['price_per_month'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $kebele = trim($_POST['kebele'] ?? '');
    $zone = trim($_POST['zone'] ?? '');
    $amenities = array_filter(array_map('trim', $_POST['amenities'] ?? []));

    // Validation
    if (!$title) $errors[] = "Title is required.";
    if (strlen($title) > 100) $errors[] = "Title must be 100 characters or less.";
    if (!$location) $errors[] = "Location is required.";
    if (strlen($location) > 255) $errors[] = "Location must be 255 characters or less.";
    if ($address_detail && strlen($address_detail) > 255) $errors[] = "Address detail must be 255 characters or less.";
    if (!$owner_id) $errors[] = "Owner is required.";
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $owner_id)) {
        $errors[] = "Invalid owner ID format.";
    }
    if (!is_numeric($bedrooms) || $bedrooms < 0) $errors[] = "Valid number of bedrooms is required.";
    if (!is_numeric($bathrooms) || $bathrooms < 0) $errors[] = "Valid number of bathrooms is required.";
    if (!is_numeric($price_per_month) || $price_per_month <= 0) $errors[] = "Valid price is required.";
    if (!in_array($status, ['available', 'reserved', 'rented', 'under_maintenance'])) $errors[] = "Invalid status.";
    if ($kebele && strlen($kebele) > 100) $errors[] = "Kebele must be 100 characters or less.";
    if ($zone && strlen($zone) > 100) $errors[] = "Zone must be 100 characters or less.";
    foreach ($amenities as $amenity) {
        if (strlen($amenity) > 100) $errors[] = "Each amenity name must be 100 characters or less.";
    }

    // Image upload
    $photo_urls = [];
    if (!empty($_FILES['images']['name'][0])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        foreach ($_FILES['images']['name'] as $index => $name) {
            if (!$name) continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $errors[] = "Only JPG, JPEG, PNG files are allowed for images.";
            } elseif ($_FILES['images']['size'][$index] > 5 * 1024 * 1024) {
                $errors[] = "Each image must be less than 5MB.";
            } else {
                $filename = 'property_' . uniqid() . '.' . $ext;
                $uploadPath = 'Uploads/' . $filename;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$index], $uploadPath)) {
                    $photo_urls[] = $filename;
                } else {
                    $errors[] = "Failed to upload image: $name.";
                }
            }
        }
    }

    if (!$errors) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Insert property
           // Generate UUID in PHP
$property_id = bin2hex(random_bytes(16)); // or use a library for a real UUID v4

// Format like a standard UUID: 8-4-4-4-12
$property_id = substr($property_id, 0, 8) . '-' .
               substr($property_id, 8, 4) . '-' .
               substr($property_id, 12, 4) . '-' .
               substr($property_id, 16, 4) . '-' .
               substr($property_id, 20, 12);

// Prepare insert with manually set UUID
$stmt = $conn->prepare("
    INSERT INTO properties (property_id, title, description, location, address_detail, owner_id, bedrooms, bathrooms, price_per_month, status, kebele, zone)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    throw new Exception("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ssssssiiisss", $property_id, $title, $description, $location, $address_detail, $owner_id, $bedrooms, $bathrooms, $price_per_month, $status, $kebele, $zone);
$stmt->execute();
$stmt->close();


            if (!$property_id) {
                throw new Exception("Failed to retrieve property ID.");
            }

            // Insert photos
            if ($photo_urls) {
                $stmt = $conn->prepare("INSERT INTO property_photos (photo_id, property_id, photo_url) VALUES (UUID(), ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                foreach ($photo_urls as $photo_url) {
                    $stmt->bind_param("ss", $property_id, $photo_url);
                    $stmt->execute();
                }
                $stmt->close();
            }

            // Insert amenities
            if ($amenities) {
                $stmt = $conn->prepare("INSERT INTO amenities (amenity_id, property_id, name) VALUES (UUID(), ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                foreach ($amenities as $amenity) {
                    $stmt->bind_param("ss", $property_id, $amenity);
                    $stmt->execute();
                }
                $stmt->close();
            }

            // Commit transaction
            $conn->commit();
            $_SESSION['success_message'] = "Property added successfully.";
            header("Location: manage_properties.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error adding property: " . $e->getMessage();
        }
    }
}

// Fetch owners
try {
    $owners = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'owner' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $errors[] = "Error fetching owners: " . $e->getMessage();
    $owners = [];
}
?>

<div class="main-content">
    <div class="header">
        <h1>Add New Property</h1>
        <a href="manage_properties.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php echo implode("<br>", array_map('htmlspecialchars', $errors)); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" id="addPropertyForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" maxlength="100" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" maxlength="255" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address_detail" class="form-label">Address Detail</label>
                        <input type="text" class="form-control" id="address_detail" name="address_detail" value="<?php echo htmlspecialchars($address_detail); ?>" maxlength="255">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="owner_id" class="form-label">Owner *</label>
                        <select class="form-select" id="owner_id" name="owner_id" required>
                            <option value="">Select Owner</option>
                            <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['user_id']; ?>" <?php echo $owner_id === $owner['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($owner['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="bedrooms" class="form-label">Bedrooms *</label>
                        <input type="number" class="form-control" id="bedrooms" name="bedrooms" value="<?php echo htmlspecialchars($bedrooms); ?>" min="0" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="bathrooms" class="form-label">Bathrooms *</label>
                        <input type="number" class="form-control" id="bathrooms" name="bathrooms" value="<?php echo htmlspecialchars($bathrooms); ?>" min="0" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="price_per_month" class="form-label">Price per Month ($) *</label>
                        <input type="number" class="form-control" id="price_per_month" name="price_per_month" value="<?php echo htmlspecialchars($price_per_month); ?>" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="reserved" <?php echo $status === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                            <option value="rented" <?php echo $status === 'rented' ? 'selected' : ''; ?>>Rented</option>
                            <option value="under_maintenance" <?php echo $status === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="kebele" class="form-label">Kebele</label>
                        <input type="text" class="form-control" id="kebele" name="kebele" value="<?php echo htmlspecialchars($kebele); ?>" maxlength="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="zone" class="form-label">Zone</label>
                        <input type="text" class="form-control" id="zone" name="zone" value="<?php echo htmlspecialchars($zone); ?>" maxlength="100">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Images (Max 5, JPG/PNG, <5MB each)</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/jpeg,image/png">
                        <small class="form-text text-muted">Hold Ctrl to select multiple images.</small>
                        <div id="imagePreview" class="mt-2"></div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Amenities</label>
                        <div id="amenitiesContainer">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="amenities[]" placeholder="e.g., Wi-Fi, Parking" maxlength="100">
                                <button type="button" class="btn btn-outline-danger remove-amenity">Remove</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addAmenity">Add Amenity</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Property</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addPropertyForm').addEventListener('submit', function(e) {
    const price = document.getElementById('price_per_month').value;
    if (price <= 0) {
        e.preventDefault();
        alert('Price per month must be greater than 0.');
    }
    const images = document.getElementById('images').files;
    if (images.length > 5) {
        e.preventDefault();
        alert('You can upload a maximum of 5 images.');
    }
});

document.getElementById('addAmenity').addEventListener('click', function() {
    const container = document.getElementById('amenitiesContainer');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="amenities[]" placeholder="e.g., Wi-Fi, Parking" maxlength="100">
        <button type="button" class="btn btn-outline-danger remove-amenity">Remove</button>
    `;
    container.appendChild(div);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-amenity')) {
        if (document.querySelectorAll('#amenitiesContainer .input-group').length > 1) {
            e.target.closest('.input-group').remove();
        }
    }
});

document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    const files = e.target.files;
    if (files.length > 5) {
        alert('You can upload a maximum of 5 images.');
        e.target.value = '';
        return;
    }
    for (const file of files) {
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            alert('Only JPG and PNG images are allowed.');
            e.target.value = '';
            preview.innerHTML = '';
            return;
        }
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '100px';
        img.style.margin = '5px';
        preview.appendChild(img);
    }
});
</script>

<style>
#imagePreview img {
    max-height: 100px;
    object-fit: cover;
    border-radius: 5px;
}
</style>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>