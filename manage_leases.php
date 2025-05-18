<?php
$pageTitle = "Manage Leases";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get leases with tenant and property info
$leaseQuery = "
    SELECT l.*, 
           t.full_name as tenant_name, 
           p.title as property_title,
           p.price_per_month,
           (SELECT COUNT(*) FROM invoices i WHERE i.lease_id = l.lease_id) as invoice_count
    FROM leases l
    JOIN users t ON l.tenant_id = t.user_id
    JOIN properties p ON l.property_id = p.property_id
    ORDER BY l.created_at DESC
";
$leases = $conn->query($leaseQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Leases</h1>
        <a href="add_lease.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Lease
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Leases</h5>
            <div class="filter-options">
                <select class="form-select form-select-sm" id="leaseStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="terminated">Terminated</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="leasesTable">
                    <thead>
                        <tr>
                            <th>Lease ID</th>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Term</th>
                            <th>Monthly Rent</th>
                            <th>Invoices</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leases as $lease): ?>
                        <tr data-status="<?php echo $lease['status']; ?>">
                            <td><?php echo substr($lease['lease_id'], 0, 8); ?></td>
                            <td><?php echo htmlspecialchars($lease['property_title']); ?></td>
                            <td><?php echo htmlspecialchars($lease['tenant_name']); ?></td>
                            <td>
                                <?php echo date('M j, Y', strtotime($lease['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($lease['end_date'])); ?>
                            </td>
                            <td>$<?php echo number_format($lease['monthly_rent'], 2); ?></td>
                            <td><?php echo $lease['invoice_count']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $lease['status'] === 'active' ? 'success' : 
                                         ($lease['status'] === 'pending' ? 'warning' : 
                                         ($lease['status'] === 'terminated' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($lease['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($lease['created_at'])); ?></td>
                            <td>
                                <a href="view_lease.php?id=<?php echo $lease['lease_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_lease.php?id=<?php echo $lease['lease_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="generate_invoice.php?lease_id=<?php echo $lease['lease_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-invoice"></i>
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
    $('#leaseStatusFilter').change(function() {
        const status = $(this).val();
        if (status) {
            $('#leasesTable tbody tr').hide();
            $(`#leasesTable tbody tr[data-status="${status}"]`).show();
        } else {
            $('#leasesTable tbody tr').show();
        }
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>