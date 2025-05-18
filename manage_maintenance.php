<?php
$pageTitle = "Manage Maintenance";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get maintenance requests with property and tenant info
$maintenanceQuery = "
    SELECT m.*, 
           p.title as property_title,
           t.full_name as tenant_name,
           l.lease_id
    FROM maintenance_requests m
    JOIN properties p ON m.property_id = p.property_id
    LEFT JOIN leases l ON m.lease_id = l.lease_id
    LEFT JOIN users t ON l.tenant_id = t.user_id
    ORDER BY m.request_date DESC
";
$maintenanceRequests = $conn->query($maintenanceQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Maintenance</h1>
        <a href="create_maintenance.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Request
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Maintenance Requests</h5>
            <div class="filter-options">
                <select class="form-select form-select-sm" id="maintenanceStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="maintenanceTable">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Description</th>
                            <th>Requested On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenanceRequests as $request): ?>
                        <tr data-status="<?php echo $request['status']; ?>">
                            <td><?php echo substr($request['request_id'], 0, 8); ?></td>
                            <td><?php echo htmlspecialchars($request['property_title']); ?></td>
                            <td><?php echo htmlspecialchars($request['tenant_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php echo strlen($request['description']) > 50 ? 
                                    substr(htmlspecialchars($request['description']), 0, 50) . '...' : 
                                    htmlspecialchars($request['description']); 
                                ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($request['request_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $request['status'] === 'resolved' ? 'success' : 
                                         ($request['status'] === 'in_progress' ? 'primary' : 'warning'); 
                                ?>">
                                    <?php 
                                    $statusNames = [
                                        'pending' => 'Pending',
                                        'in_progress' => 'In Progress',
                                        'resolved' => 'Resolved'
                                    ];
                                    echo $statusNames[$request['status']] ?? ucfirst($request['status']); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_maintenance.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_maintenance.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="update_status.php?id=<?php echo $request['request_id']; ?>&status=resolved" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Filter by status
    $('#maintenanceStatusFilter').change(function() {
        const status = $(this).val();
        if (status) {
            $('#maintenanceTable tbody tr').hide();
            $(`#maintenanceTable tbody tr[data-status="${status}"]`).show();
        } else {
            $('#maintenanceTable tbody tr').show();
        }
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>