<?php
$pageTitle = "View User";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$userId = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("s", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: manage_users.php");
    exit;
}

// Get related data (example: properties, bookings, activity logs)
$propertiesStmt = $conn->prepare("SELECT title, status FROM properties WHERE owner_id = ? LIMIT 5");
$propertiesStmt->bind_param("s", $userId);
$propertiesStmt->execute();
$properties = $propertiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$propertiesStmt->close();

$bookingsStmt = $conn->prepare("SELECT booking_id, status, start_date FROM bookings WHERE tenant_id = ? ORDER BY booking_date DESC LIMIT 5");
$bookingsStmt->bind_param("s", $tenantId);  
$bookingsStmt->execute();
$bookings = $bookingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$bookingsStmt->close();


$activityStmt = $conn->prepare("SELECT activity_type, description, created_at FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activityStmt->bind_param("s", $userId);
$activityStmt->execute();
$activities = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$activityStmt->close();
?>

<div class="main-content">
    <div class="header">
        <h1>View User: <?php echo htmlspecialchars($user['full_name']); ?></h1>
        <a href="manage_users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">User Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <img src="Uploads/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.jpg'); ?>" 
                         class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px;">
                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                </div>
                <div class="col-md-8">
                    <dl class="row">
                        <dt class="col-sm-3">Email:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($user['email']); ?></dd>
                        <dt class="col-sm-3">Phone:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></dd>
                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-<?php 
                                echo $user['status'] === 'active' ? 'success' : 
                                     ($user['status'] === 'inactive' ? 'secondary' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </dd>
                        <dt class="col-sm-3">Joined:</dt>
                        <dd class="col-sm-9"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></dd>
                        <dt class="col-sm-3">Last Updated:</dt>
                        <dd class="col-sm-9"><?php echo $user['updated_at'] ? date('M j, Y', strtotime($user['updated_at'])) : 'N/A'; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Related Properties</h5>
        </div>
        <div class="card-body">
            <?php if ($properties): ?>
            <ul class="list-group">
                <?php foreach ($properties as $property): ?>
                <li class="list-group-item">
                    <?php echo htmlspecialchars($property['title']); ?> 
                    <span class="badge bg-<?php 
                        echo $property['status'] === 'available' ? 'success' : 
                             ($property['status'] === 'rented' ? 'primary' : 'warning'); 
                    ?>">
                        <?php echo ucfirst($property['status']); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">No properties found.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Recent Bookings</h5>
        </div>
        <div class="card-body">
            <?php if ($bookings): ?>
            <ul class="list-group">
                <?php foreach ($bookings as $booking): ?>
                <li class="list-group-item">
                    Booking ID: <?php echo $booking['booking_id']; ?> 
                    <span class="badge bg-<?php 
                        echo $booking['status'] === 'confirmed' ? 'success' : 
                             ($booking['status'] === 'pending' ? 'warning' : 'danger'); 
                    ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                    <small class="text-muted"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">No bookings found.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Recent Activity</h5>
        </div>
        <div class="card-body">
            <?php if ($activities): ?>
            <ul class="list-group">
                <?php foreach ($activities as $activity): ?>
                <li class="list-group-item">
                    <strong><?php echo htmlspecialchars($activity['activity_type']); ?></strong>: 
                    <?php echo htmlspecialchars($activity['description']); ?>
                    <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">No activity found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>