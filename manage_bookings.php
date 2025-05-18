<?php
$pageTitle = "Manage Bookings";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get bookings with tenant and property info
$bookingQuery = "
    SELECT b.*, 
           t.full_name as tenant_name, 
           p.title as property_title,
           p.price_per_month
    FROM bookings b
    JOIN users t ON b.tenant_id = t.user_id
    JOIN properties p ON b.property_id = p.property_id
    ORDER BY b.booking_date DESC
";
$bookings = $conn->query($bookingQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Bookings</h1>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-plus"></i> Add New
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="add_booking.php">Manual Booking</a></li>
                <li><a class="dropdown-item" href="import_bookings.php">Import Bookings</a></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Bookings</h5>
            <div class="filter-options">
                <select class="form-select form-select-sm" id="bookingStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="bookingsTable">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Dates</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr data-status="<?php echo $booking['status']; ?>">
                            <td><?php echo substr($booking['booking_id'], 0, 8); ?></td>
                            <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                            <td><?php echo htmlspecialchars($booking['tenant_name']); ?></td>
                            <td>
                                <?php echo date('M j', strtotime($booking['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                            </td>
                            <td>$<?php echo number_format($booking['price_per_month'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $booking['status'] === 'confirmed' ? 'success' : 
                                         ($booking['status'] === 'pending' ? 'warning' : 
                                         ($booking['status'] === 'cancelled' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
                            <td>
                                <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger delete-booking" data-id="<?php echo $booking['booking_id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
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
    $('#bookingStatusFilter').change(function() {
        const status = $(this).val();
        if (status) {
            $('#bookingsTable tbody tr').hide();
            $(`#bookingsTable tbody tr[data-status="${status}"]`).show();
        } else {
            $('#bookingsTable tbody tr').show();
        }
    });

    $('.delete-booking').click(function() {
        if (confirm('Are you sure you want to delete this booking?')) {
            const bookingId = $(this).data('id');
            window.location.href = 'delete_booking.php?id=' + bookingId;
        }
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>