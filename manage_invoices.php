<?php
$pageTitle = "Manage Invoices";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get invoices with lease and tenant info
$invoiceQuery = "
    SELECT i.*, 
           l.start_date, l.end_date,
           t.full_name as tenant_name,
           p.title as property_title
    FROM invoices i
    JOIN leases l ON i.lease_id = l.lease_id
    JOIN users t ON l.tenant_id = t.user_id
    JOIN properties p ON l.property_id = p.property_id
    ORDER BY i.invoice_date DESC
";
$invoices = $conn->query($invoiceQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Invoices</h1>
        <a href="generate_invoice.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Generate Invoice
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Invoices</h5>
            <div class="filter-options">
                <select class="form-select form-select-sm" id="invoiceStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="invoicesTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Tenant</th>
                            <th>Property</th>
                            <th>Lease Term</th>
                            <th>Amount Due</th>
                            <th>Amount Paid</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr data-status="<?php echo $invoice['status']; ?>">
                            <td>INV-<?php echo substr($invoice['invoice_id'], 0, 8); ?></td>
                            <td><?php echo htmlspecialchars($invoice['tenant_name']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['property_title']); ?></td>
                            <td>
                                <?php echo date('M j, Y', strtotime($invoice['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($invoice['end_date'])); ?>
                            </td>
                            <td>$<?php echo number_format($invoice['amount_due'], 2); ?></td>
                            <td>$<?php echo number_format($invoice['amount_paid'], 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($invoice['due_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $invoice['status'] === 'paid' ? 'success' : 
                                         ($invoice['status'] === 'partial' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($invoice['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="print_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="fas fa-print"></i>
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
    $('#invoiceStatusFilter').change(function() {
        const status = $(this).val();
        if (status) {
            $('#invoicesTable tbody tr').hide();
            $(`#invoicesTable tbody tr[data-status="${status}"]`).show();
        } else {
            $('#invoicesTable tbody tr').show();
        }
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>