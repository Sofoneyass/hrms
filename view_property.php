<?php
$pageTitle = "View Property";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Property ID not provided.";
    header("Location: manage_properties.php");
    exit;
}

$propertyId = $_GET['id'];
try {
    // Fetch property details
    $stmt = $conn->prepare("
        SELECT p.*, u.full_name as owner_name 
        FROM properties p
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE p.property_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$property) {
        throw new Exception("Property not found.");
    }

    // Fetch property photos
    $stmt = $conn->prepare("SELECT photo_id, photo_url FROM property_photos WHERE property_id = ? ORDER BY uploaded_at");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Validate photo paths
    $photoPaths = [];
    foreach ($photos as &$photo) {
        $path = "Uploads/" . $photo['photo_url'];
        if (!file_exists($path) || !is_file($path)) {
            $photo['photo_url'] = "default_property.jpg";
            $path = "Uploads/default_property.jpg";
        }
        $photoPaths[] = $path;
    }
    // Fallback if no photos
    if (empty($photoPaths)) {
        $photoPaths[] = "Uploads/default_property.jpg";
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: manage_properties.php");
    exit;
}
?>

<div class="main-content">
    <div class="header">
        <h1>View Property: <?php echo htmlspecialchars($property['title']); ?></h1>
        <a href="manage_properties.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Property Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="gallery">
                        <?php foreach ($photoPaths as $index => $path): ?>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#photoModal" data-photo="<?php echo htmlspecialchars($path); ?>">
                            <img src="<?php echo htmlspecialchars($path); ?>" 
                                 class="img-fluid rounded mb-2 shadow-sm hover-zoom" 
                                 style="max-height: 150px; object-fit: cover; width: 100%;" 
                                 alt="Property Photo <?php echo $index + 1; ?>">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <dl class="row">
                        <dt class="col-sm-3">Title:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($property['title']); ?></dd>
                        <dt class="col-sm-3">Description:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($property['description'] ?? 'N/A'); ?></dd>
                        <dt class="col-sm-3">Location:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($property['location']); ?></dd>
                        <dt class="col-sm-3">Address Detail:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($property['address_detail'] ?? 'N/A'); ?></dd>
                        <dt class="col-sm-3">Kebele:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($property['kebele'] ?? 'N/A'); ?></dd>
                        <dt class="col-sm-3">Zone:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($property['zone'] ?? 'N/A'); ?></dd>
                        <dt class="col-sm-3">Owner:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($property['owner_name'] ?? 'N/A'); ?></dd>
                        <dt class="col-sm-3">Bedrooms:</dt>
                        <dd class="col-sm-9"><?php echo $property['bedrooms'] ?? 'N/A'; ?></dd>
                        <dt class="col-sm-3">Bathrooms:</dt>
                        <dd class="col-sm-9"><?php echo $property['bathrooms'] ?? 'N/A'; ?></dd>
                        <dt class="col-sm-3">Price per Month:</dt>
                        <dd class="col-sm-9">$<?php echo number_format($property['price_per_month'], 2); ?></dd>
                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-<?php 
                                echo $property['status'] === 'available' ? 'success' : 
                                     ($property['status'] === 'reserved' ? 'info' : 
                                     ($property['status'] === 'rented' ? 'primary' : 
                                     ($property['status'] === 'under_maintenance' ? 'warning' : 'secondary'))); 
                            ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </span>
                        </dd>
                        <dt class="col-sm-3">Created:</dt>
                        <dd class="col-sm-9"><?php echo date('M j, Y', strtotime($property['created_at'])); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Property Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhoto" src="" class="img-fluid" style="max-height: 500px; object-fit: contain;" alt="Property Photo">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoLinks = document.querySelectorAll('[data-bs-target="#photoModal"]');
    photoLinks.forEach(link => {
        link.addEventListener('click', function() {
            const photoSrc = this.getAttribute('data-photo');
            document.getElementById('modalPhoto').src = photoSrc;
        });
    });
});
</script>

<style>
.gallery img:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}
</style>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>