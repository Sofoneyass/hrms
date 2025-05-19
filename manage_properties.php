<?php
$pageTitle = "Manage Properties";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get session messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Initialize filter parameters
$filters = [
    'title' => $_GET['title'] ?? '',
    'status' => $_GET['status'] ?? '',
    'owner_id' => $_GET['owner_id'] ?? '',
];

// Build query
$whereClauses = [];
$params = [];
$paramTypes = '';

if ($filters['title']) {
    $whereClauses[] = "p.title LIKE ?";
    $params[] = '%' . $filters['title'] . '%';
    $paramTypes .= 's';
}
if ($filters['status']) {
    $whereClauses[] = "p.status = ?";
    $params[] = $filters['status'];
    $paramTypes .= 's';
}
if ($filters['owner_id']) {
    $whereClauses[] = "p.owner_id = ?";
    $params[] = $filters['owner_id'];
    $paramTypes .= 's';
}

$whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    // Get properties
    $propertyQuery = "
        SELECT p.*, u.full_name as owner_name 
        FROM properties p
        LEFT JOIN users u ON p.owner_id = u.user_id
        $whereSql
        ORDER BY p.created_at DESC
    ";
    $stmt = $conn->prepare($propertyQuery);
    if ($paramTypes) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get unique owners
    $ownerQuery = "SELECT DISTINCT u.user_id, u.full_name FROM users u JOIN properties p ON u.user_id = p.owner_id ORDER BY u.full_name";
    $owners = $conn->query($ownerQuery)->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    $properties = [];
    $owners = [];
}
?>

<style>
    .glass { 
        background: rgba(255, 255, 255, 0.1); 
        backdrop-filter: blur(10px); 
        border: 1px solid rgba(255, 255, 255, 0.2); 
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
    }
    .table th, .table td { padding: 8px; font-size: 0.9rem; }
    .btn-sm { padding: 4px 8px; }
    input, select { transition: all 0.2s; }
    input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
    .spinner { 
        display: none; 
        border: 3px solid #f3f3f3; 
        border-top: 3px solid #16a34a; 
        border-radius: 50%; 
        width: 16px; 
        height: 16px; 
        animation: spin 1s linear infinite; 
        margin: 0 4px; 
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<div class="main-content p-4">
    <div class="header flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Properties</h1>
        <div class="flex space-x-2">
            <button id="toggleFilters" class="btn btn-secondary text-sm"><i class="fas fa-filter"></i> Filters</button>
            <a href="add_property.php" class="btn btn-primary text-sm"><i class="fas fa-plus"></i> Add</a>
            <button id="exportAllPdf" class="btn btn-success text-sm"><i class="fas fa-file-pdf"></i> Export All to PDF<span id="exportAllSpinner" class="spinner"></span></button>
        </div>
    </div>

    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show text-sm" role="alert">
        <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show text-sm" role="alert">
        <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div id="filterPanel" class="card glass mb-4 hidden">
        <div class="card-body p-3">
            <form id="filterForm" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium">Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($filters['title']); ?>" 
                           class="w-full p-1 rounded bg-gray-200 text-black border border-gray-400 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" 
                           autofocus>
                </div>
                <div>
                    <label class="block text-xs font-medium">Status</label>
                    <select name="status" class="w-full p-1 rounded bg-gray-200 text-black border border-gray-400 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">All</option>
                        <option value="available" <?php echo $filters['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="rented" <?php echo $filters['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                        <option value="under_maintenance" <?php echo $filters['status'] === 'under_maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium">Owner</label>
                    <select name="owner_id" class="w-full p-1 rounded bg-gray-200 text-black border border-gray-400 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Any</option>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['user_id']; ?>" 
                                    <?php echo $filters['owner_id'] === $owner['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($owner['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-3 flex space-x-2">
                    <button type="submit" class="btn btn-primary w-full text-sm"><i class="fas fa-filter"></i> Apply</button>
                    <button type="button" id="clearFilters" class="btn btn-secondary w-full text-sm"><i class="fas fa-times"></i> Clear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Properties Table -->
    <div class="card glass">
        <div class="card-header p-3">
            <h5 class="text-sm font-semibold">All Properties (<?php echo count($properties); ?>)</h5>
        </div>
        <div class="card-body p-3">
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
                                <td>$<?php echo number_format($property['price_per_month'], 0); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $property['status'] === 'available' ? 'success' : 
                                             ($property['status'] === 'rented' ? 'primary' : 
                                             ($property['status'] === 'under_maintenance' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($property['status']); ?>
                                    </span>
                                </td>
                                <td class="flex space-x-1">
                                    <a href="view_property.php?id=<?php echo $property['property_id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <button class="btn btn-sm btn-danger delete-property" 
                                            data-id="<?php echo $property['property_id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($property['title']); ?>"><i class="fas fa-trash"></i></button>
                                    <button class="btn btn-sm btn-success generate-report" 
                                            data-id="<?php echo $property['property_id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($property['title']); ?>"><i class="fas fa-file-pdf"></i><span class="spinner"></span></button>
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
        <div class="modal-content glass">
            <div class="modal-header">
                <h5 class="modal-title text-sm" id="deletePropertyModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-sm">
                Delete <strong id="deletePropertyTitle"></strong>? This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary text-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deletePropertyForm" method="POST" action="delete_property.php">
                    <input type="hidden" name="property_id" id="deletePropertyId">
                    <button type="submit" class="btn btn-danger text-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Report Generation Modal -->
<div class="modal fade" id="generateReportModal" tabindex="-1" aria-labelledby="generateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass">
            <div class="modal-header">
                <h5 class="modal-title text-sm" id="generateReportModalLabel">Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-sm">
                Generate PDF for <strong id="generateReportTitle"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary text-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="generateReportForm" method="POST" action="generate_property_report.php">
                    <input type="hidden" name="property_id" id="generateReportId">
                    <button type="submit" class="btn btn-success text-sm">Generate</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle filters
    $('#toggleFilters').click(function() {
        $('#filterPanel').toggleClass('hidden');
    });

    // Clear filters
    $('#clearFilters').click(function() {
        $('#filterForm')[0].reset();
        window.location.href = 'manage_properties.php';
    });

    // Delete modal
    $('.delete-property').click(function() {
        $('#deletePropertyTitle').text($(this).data('title'));
        $('#deletePropertyId').val($(this).data('id'));
        $('#deletePropertyModal').modal('show');
    });

    // Single report modal
    $('.generate-report').click(function() {
        $('#generateReportTitle').text($(this).data('title'));
        $('#generateReportId').val($(this).data('id'));
        $('#generateReportModal').modal('show');
        $(this).find('.spinner').show();
        $('#generateReportForm').submit(function() {
            setTimeout(() => $(this).find('.spinner').hide(), 1000);
        });
    });

    // Overall report
    $('#exportAllPdf').click(function() {
        $('#exportAllSpinner').show();
        window.location.href = 'generate_overall_report.php?' + $('#filterForm').serialize();
        setTimeout(() => $('#exportAllSpinner').hide(), 1000);
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>