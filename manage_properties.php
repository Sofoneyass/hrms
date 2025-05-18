<?php
$pageTitle = "Manage Properties";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get session messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

try {
    // Get properties with owner info
    $propertyQuery = "
        SELECT p.*, u.full_name as owner_name 
        FROM properties p
        LEFT JOIN users u ON p.owner_id = u.user_id
        ORDER BY p.created_at DESC
    ";
    $result = $conn->query($propertyQuery);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    $properties = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    $properties = [];
}
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Properties</h1>
        <a href="add_property.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Property
        </a>
    </div>

    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Properties</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Owner</th>
                            <th>Bed/Bath</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($properties): ?>
                            <?php foreach ($properties as $property): ?>
                            <tr>
                                <td><?php echo substr($property['property_id'], 0, 8); ?></td>
                                <td><?php echo htmlspecialchars($property['title']); ?></td>
                                <td><?php echo htmlspecialchars($property['location']); ?></td>
                                <td><?php echo htmlspecialchars($property['owner_name'] ?? 'N/A'); ?></td>
                                <td><?php echo $property['bedrooms']; ?> / <?php echo $property['bathrooms']; ?></td>
                                <td>$<?php echo number_format($property['price_per_month'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $property['status'] === 'available' ? 'success' : 
                                             ($property['status'] === 'rented' ? 'primary' : 
                                             ($property['status'] === 'under_maintenance' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($property['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_property.php?id=<?php echo $property['property_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_property.php?id=<?php echo $property['property_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger delete-property" 
                                            data-id="<?php echo $property['property_id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($property['title']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No properties found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1" aria-labelledby="deletePropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePropertyModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="deletePropertyTitle"></strong>? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deletePropertyForm" method="POST" action="delete_property.php">
                    <input type="hidden" name="property_id" id="deletePropertyId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    $('.delete-property').click(function() {
        const propertyId = $(this).data('id');
        const propertyTitle = $(this).data('title');
        $('#deletePropertyTitle').text(propertyTitle);
        $('#deletePropertyId').val(propertyId);
        $('#deletePropertyModal').modal('show');
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>