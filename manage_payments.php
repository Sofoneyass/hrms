<?php
$pageTitle = "Manage Payments";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get payments with invoice and tenant info
$paymentQuery = "
    SELECT p.*, 
           i.invoice_id,
           t.full_name as tenant_name,
           l.property_id,
           prop.title as property_title
    FROM payments p
    JOIN invoices i ON p.lease_id = i.lease_id
    JOIN leases l ON p.lease_id = l.lease_id
    JOIN users t ON p.user_id = t.user_id
    JOIN properties prop ON l.property_id = prop.property_id
    ORDER BY p.payment_date DESC
";
$payments = $conn->query($paymentQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Payments</h1>
        <a href="record_payment.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Record Payment
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Payments</h5>
            <div class="filter-options">
                <select class="form-select form-select-sm" id="paymentMethodFilter">
                    <option value="">All Methods</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="chapa">Chapa</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Tenant</th>
                            <th>Property</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Receipt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr data-method="<?php echo $payment['payment_method']; ?>">
                            <td><?php echo substr($payment['payment_id'], 0, 8); ?></td>
                            <td><?php echo htmlspecialchars($payment['tenant_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['property_title']); ?></td>
                            <td>INV-<?php echo substr($payment['invoice_id'], 0, 8); ?></td>
                            <td>$<?php echo number_format($payment['amount_paid'], 2); ?></td>
                            <td>
                                <?php 
                                $methodNames = [
                                    'credit_card' => 'Credit Card',
                                    'bank_transfer' => 'Bank Transfer',
                                    'cash' => 'Cash',
                                    'chapa' => 'Chapa'
                                ];
                                echo $methodNames[$payment['payment_method']] ?? ucfirst($payment['payment_method']); 
                                ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $payment['payment_status'] === 'completed' ? 'success' : 
                                         ($payment['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($payment['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $payment['receipt_number'] ?? 'N/A'; ?></td>
                            <td>
                                <a href="view_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="print_receipt.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
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
    // Filter by payment method
    $('#paymentMethodFilter').change(function() {
        const method = $(this).val();
        if (method) {
            $('#paymentsTable tbody tr').hide();
            $(`#paymentsTable tbody tr[data-method="${method}"]`).show();
        } else {
            $('#paymentsTable tbody tr').show();
        }
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>