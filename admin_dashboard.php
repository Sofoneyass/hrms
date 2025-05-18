<?php
$pageTitle = "Dashboard";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';
// Get admin info
$adminId = $_SESSION['user_id'];
$adminStmt = $conn->prepare("SELECT full_name, email, phone, role, profile_image FROM users WHERE user_id = ?");
$adminStmt->bind_param("s", $adminId);
$adminStmt->execute();
$adminResult = $adminStmt->get_result();
$admin = $adminResult->fetch_assoc();
$adminStmt->close();
// Function to safely get counts
function getCount($conn, $table) {
    $allowedTables = ['users', 'properties', 'bookings', 'leases', 'invoices', 'payments', 'maintenance_requests', 'messages', 'notifications'];
    if (!in_array($table, $allowedTables)) return 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $table");
    if (!$stmt) return 0;
    
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    $stmt->close();
    return $count;
}

$counts = [
    'users' => getCount($conn, 'users'),
    'properties' => getCount($conn, 'properties'),
    'bookings' => getCount($conn, 'bookings'),
    'leases' => getCount($conn, 'leases'),
    'invoices' => getCount($conn, 'invoices'),
    'payments' => getCount($conn, 'payments'),
    'maintenance_requests' => getCount($conn, 'maintenance_requests'),
    'messages' => getCount($conn, 'messages'),
    'notifications' => getCount($conn, 'notifications')
];

// Get recent activities
$activityStmt = $conn->prepare("SELECT a.activity_type, a.description, a.created_at, u.full_name FROM activity_logs a LEFT JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 5");
$activityStmt->execute();
$activities = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$activityStmt->close();

// Get pending maintenance requests
$maintenanceStmt = $conn->prepare("SELECT m.request_id, m.description, m.request_date, p.title as property_title FROM maintenance_requests m JOIN properties p ON m.property_id = p.property_id WHERE m.status = 'pending' ORDER BY m.request_date DESC LIMIT 5");
$maintenanceStmt->execute();
$maintenanceRequests = $maintenanceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$maintenanceStmt->close();

// Get data for visualizations
// 1. User Growth (monthly registrations)
$userGrowthStmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM users GROUP BY month ORDER BY month DESC LIMIT 12");
$userGrowthStmt->execute();
$userGrowthData = $userGrowthStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$userGrowthStmt->close();
$userGrowthLabels = array_column(array_reverse($userGrowthData), 'month');
$userGrowthCounts = array_column(array_reverse($userGrowthData), 'count');

// 2. Property Status Distribution
$propertyStatusStmt = $conn->prepare("SELECT status, COUNT(*) as count FROM properties GROUP BY status");
$propertyStatusStmt->execute();
$propertyStatusData = $propertyStatusStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$propertyStatusStmt->close();
$propertyStatusLabels = array_column($propertyStatusData, 'status');
$propertyStatusCounts = array_column($propertyStatusData, 'count');

// 3. User Role Distribution
$userRoleStmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$userRoleStmt->execute();
$userRoleData = $userRoleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$userRoleStmt->close();
$userRoleLabels = array_column($userRoleData, 'role');
$userRoleCounts = array_column($userRoleData, 'count');

// 4. Property Price Ranges
$priceRangeStmt = $conn->prepare("SELECT 
    CASE 
        WHEN price_per_month < 5000 THEN '< 5000'
        WHEN price_per_month BETWEEN 5000 AND 10000 THEN '5000-10000'
        WHEN price_per_month BETWEEN 10001 AND 20000 THEN '10001-20000'
        ELSE '> 20000'
    END as price_range,
    COUNT(*) as count
    FROM properties
    GROUP BY price_range
    ORDER BY price_range");
$priceRangeStmt->execute();
$priceRangeData = $priceRangeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$priceRangeStmt->close();
$priceRangeLabels = array_column($priceRangeData, 'price_range');
$priceRangeCounts = array_column($priceRangeData, 'count');
?>

<div class="main-content">
    <!-- Header -->
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="user-profile">
            <img src="Uploads/<?php echo htmlspecialchars($admin['profile_image'] ?? 'default.jpg'); ?>" alt="Admin Photo">
            <div class="dropdown">
                <button class="dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($admin['full_name']); ?>
                    <i class="fas fa-caret-down ms-2"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="system_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Data Visualizations -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Growth Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="userGrowthChart" height="150"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Property Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="propertyStatusChart" height="150"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Role Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="userRoleChart" height="150"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Property Price Ranges</h5>
                </div>
                <div class="card-body">
                    <canvas id="priceRangeChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <?php
        $statCards = [
            ['users', 'fas fa-users', 'Total Users', 'primary'],
            ['properties', 'fas fa-building', 'Properties', 'success'],
            ['bookings', 'fas fa-calendar-alt', 'Bookings', 'warning'],
            ['leases', 'fas fa-file-signature', 'Active Leases', 'info'],
            ['invoices', 'fas fa-file-invoice', 'Invoices', 'secondary'],
            ['payments', 'fas fa-money-bill-alt', 'Payments', 'success'],
            ['maintenance_requests', 'fas fa-tools', 'Maintenance', 'danger'],
            ['messages', 'fas fa-envelope', 'Messages', 'primary'],
            ['notifications', 'fas fa-bell', 'Notifications', 'purple']
        ];
        
        foreach ($statCards as $card): 
            $colorClass = "text-" . $card[3];
        ?>
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="<?php echo $card[1]; ?> fa-3x mb-3 <?php echo $colorClass; ?>"></i>
                    <h2><?php echo $counts[$card[0]]; ?></h2>
                    <p><?php echo $card[2]; ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Activities and Maintenance -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activities</h5>
                    <a href="activity_logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                <span class="activity-time"><?php echo date('M j, H:i', strtotime($activity['created_at'])); ?></span>
                            </div>
                            <p class="mb-0"><?php echo htmlspecialchars($activity['description']); ?></p>
                            <small class="text-muted"><?php echo htmlspecialchars($activity['activity_type']); ?></small>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($activities)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-info-circle text-muted"></i>
                            <p class="mb-0">No recent activities</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Maintenance Requests</h5>
                    <a href="manage_maintenance.php" class="btn btn-sm btn-outline-danger">View All</a>
                </div>
                <div class="card-body">
                    <?php foreach ($maintenanceRequests as $request): ?>
                        <div class="activity-item mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($request['property_title']); ?></strong>
                                <span class="activity-time"><?php echo date('M j, H:i', strtotime($request['request_date'])); ?></span>
                            </div>
                            <p class="mb-0"><?php echo htmlspecialchars($request['description']); ?></p>
                            <span class="badge bg-warning text-dark">Pending</span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($maintenanceRequests)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle text-success"></i>
                            <p class="mb-0">No pending maintenance requests</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Chart.js configuration
const colors = {
    primary: '#1e88e5',
    success: '#00c853',
    warning: '#ffab00',
    danger: '#ff5252',
    info: '#26c6da',
    secondary: '#6c757d',
    purple: '#6f42c1'
};

// 1. User Growth Chart
new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($userGrowthLabels); ?>,
        datasets: [{
            label: 'New Users',
            data: <?php echo json_encode($userGrowthCounts); ?>,
            borderColor: colors.primary,
            backgroundColor: 'rgba(30, 136, 229, 0.2)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: colors.primary,
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: colors.primary
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { backgroundColor: colors.dark, cornerRadius: 8 }
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
        }
    }
});

// 2. Property Status Chart
new Chart(document.getElementById('propertyStatusChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($propertyStatusLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($propertyStatusCounts); ?>,
            backgroundColor: [colors.success, colors.warning, colors.danger, colors.info],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' },
            tooltip: { backgroundColor: colors.dark, cornerRadius: 8 }
        }
    }
});

// 3. User Role Chart
new Chart(document.getElementById('userRoleChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($userRoleLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($userRoleCounts); ?>,
            backgroundColor: [colors.primary, colors.secondary, colors.purple],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' },
            tooltip: { backgroundColor: colors.dark, cornerRadius: 8 }
        }
    }
});

// 4. Property Price Range Chart
new Chart(document.getElementById('priceRangeChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($priceRangeLabels); ?>,
        datasets: [{
            label: 'Properties',
            data: <?php echo json_encode($priceRangeCounts); ?>,
            backgroundColor: colors.info,
            borderColor: colors.info,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor: colors.dark, cornerRadius: 8 }
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
        }
    }
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>