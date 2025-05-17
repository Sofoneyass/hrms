<?php
// Database connection
require_once 'db_connection.php'; // Assuming you have a config file with DB connection

// Fetch all users with their role information
$users_query = "SELECT * FROM users ORDER BY role, created_at DESC";
$users_result = mysqli_query($conn, $users_query);

// Separate users into owners and tenants
$owners = [];
$tenants = [];

while ($user = mysqli_fetch_assoc($users_result)) {
    if ($user['role'] == 'owner') {
        // Get owner statistics
        $owner_stats = get_owner_stats($user['user_id'], $conn);
        $owners[] = array_merge($user, $owner_stats);
    } elseif ($user['role'] == 'tenant') {
        // Get tenant statistics
        $tenant_stats = get_tenant_stats($user['user_id'], $conn);
        $tenants[] = array_merge($user, $tenant_stats);
    }
}

// Sort owners by property count (ranking)
usort($owners, function($a, $b) {
    return $b['property_count'] - $a['property_count'];
});

// Helper function to get owner statistics
function get_owner_stats($owner_id, $conn) {
    $stats = [
        'property_count' => 0,
        'total_value' => 0,
        'booking_count' => 0,
        'average_rating' => 0,
        'verified' => false // Placeholder for verification status
    ];
    
    // Get property count and total value
    $property_query = "SELECT COUNT(*) as count, SUM(price_per_month) as total 
                      FROM properties WHERE owner_id = $owner_id";
    $property_result = mysqli_query($conn, $property_query);
    if ($property_row = mysqli_fetch_assoc($property_result)) {
        $stats['property_count'] = $property_row['count'];
        $stats['total_value'] = $property_row['total'] ? $property_row['total'] : 0;
    }
    
    // Get booking count
    $booking_query = "SELECT COUNT(*) as count FROM bookings b
                     JOIN properties p ON b.property_id = p.property_id
                     WHERE p.owner_id = $owner_id";
    $booking_result = mysqli_query($conn, $booking_query);
    if ($booking_row = mysqli_fetch_assoc($booking_result)) {
        $stats['booking_count'] = $booking_row['count'];
    }
    
    // Get average rating
    $rating_query = "SELECT AVG(r.rating) as avg_rating FROM reviews r
                    JOIN properties p ON r.property_id = p.property_id
                    WHERE p.owner_id = $owner_id";
    $rating_result = mysqli_query($conn, $rating_query);
    if ($rating_row = mysqli_fetch_assoc($rating_result)) {
        $stats['average_rating'] = round($rating_row['avg_rating'], 1);
    }
    
    return $stats;
}

// Helper function to get tenant statistics
function get_tenant_stats($tenant_id, $conn) {
    $stats = [
        'booking_count' => 0,
        'current_lease' => null,
        'reviews_count' => 0,
        'average_rating' => 0
    ];
    
    // Get booking count
    $booking_query = "SELECT COUNT(*) as count FROM bookings WHERE tenant_id = $tenant_id";
    $booking_result = mysqli_query($conn, $booking_query);
    if ($booking_row = mysqli_fetch_assoc($booking_result)) {
        $stats['booking_count'] = $booking_row['count'];
    }
    
    // Get current lease
    $lease_query = "SELECT * FROM leases 
                   WHERE tenant_id = $tenant_id AND status = 'active'
                   ORDER BY end_date DESC LIMIT 1";
    $lease_result = mysqli_query($conn, $lease_query);
    if ($lease_row = mysqli_fetch_assoc($lease_result)) {
        $stats['current_lease'] = $lease_row;
    }
    
    // Get reviews count and average rating
    $review_query = "SELECT COUNT(*) as count, AVG(rating) as avg_rating 
                    FROM reviews WHERE tenant_id = $tenant_id";
    $review_result = mysqli_query($conn, $review_query);
    if ($review_row = mysqli_fetch_assoc($review_result)) {
        $stats['reviews_count'] = $review_row['count'];
        $stats['average_rating'] = round($review_row['avg_rating'], 1);
    }
    
    return $stats;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same CSS as before */
        .badge-premium { background: linear-gradient(45deg, #FFD700, #D4AF37); color: #000; }
        .badge-top-rated { background: linear-gradient(45deg, #4e54c8, #8f94fb); color: #fff; }
        .badge-new { background: linear-gradient(45deg, #4CAF50, #8BC34A); color: #fff; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .nav-pills .nav-link.active { background-color: #3a86ff; }
        .tab-content { padding: 20px 0; }
        .progress-thin { height: 6px; }
        .rating-stars { color: #FFD700; }
        .property-count { font-weight: bold; color: #3a86ff; }
        .table-hover tbody tr:hover { background-color: rgba(58, 134, 255, 0.05); }
        .rank-badge { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; font-weight: bold; font-size: 0.8rem; }
        .rank-1 { background-color: #FFD700; color: #000; }
        .rank-2 { background-color: #C0C0C0; color: #000; }
        .rank-3 { background-color: #CD7F32; color: #000; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">User Management</h1>
            <div class="d-flex">
                <div class="input-group me-3" style="width: 300px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search users..." id="userSearch">
                </div>
                <button class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add User
                </button>
            </div>
        </div>

        <ul class="nav nav-pills mb-4" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="owners-tab" data-bs-toggle="pill" data-bs-target="#owners" type="button" role="tab">
                    Property Owners <span class="badge bg-primary ms-2"><?= count($owners) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tenants-tab" data-bs-toggle="pill" data-bs-target="#tenants" type="button" role="tab">
                    Tenants <span class="badge bg-success ms-2"><?= count($tenants) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="userTabsContent">
            <!-- Owners Tab -->
            <div class="tab-pane fade show active" id="owners" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Property Owners</h5>
                        <div class="d-flex">
                            <select class="form-select form-select-sm me-2" style="width: 150px;" id="ownerSort">
                                <option value="rank">Sort by: Rank</option>
                                <option value="properties">Most Properties</option>
                                <option value="value">Highest Value</option>
                                <option value="rating">Best Rating</option>
                                <option value="newest">Newest</option>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-filter me-1"></i>Filters
                            </button>
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Rank</th>
                                        <th style="width: 60px;"></th>
                                        <th>Owner</th>
                                        <th>Properties</th>
                                        <th>Total Value</th>
                                        <th>Bookings</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($owners as $index => $owner): ?>
                                        <tr>
                                            <td>
                                                <span class="rank-badge <?= $index < 3 ? 'rank-'.($index+1) : '' ?>">
                                                    <?= $index + 1 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <img src="<?= $owner['profile_image'] ?: 'https://randomuser.me/api/portraits/men/'.rand(1,100).'.jpg' ?>" 
                                                     class="user-avatar" alt="<?= htmlspecialchars($owner['full_name']) ?>">
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($owner['full_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($owner['email']) ?></div>
                                                <?php if ($owner['property_count'] > 10): ?>
                                                    <span class="badge badge-premium mt-1">Premium</span>
                                                <?php elseif ($owner['created_at'] > date('Y-m-d H:i:s', strtotime('-30 days'))): ?>
                                                    <span class="badge badge-new mt-1">New</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="property-count"><?= $owner['property_count'] ?></span>
                                                <div class="progress progress-thin mt-1">
                                                    <?php 
                                                    $percentage = min(100, ($owner['property_count'] / max(1, max(array_column($owners, 'property_count'))))) * 100;
                                                    ?>
                                                    <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                                                </div>
                                            </td>
                                            <td>$<?= number_format($owner['total_value'], 2) ?></td>
                                            <td><?= $owner['booking_count'] ?> bookings</td>
                                            <td>
                                                <div class="rating-stars">
                                                    <?= str_repeat('<i class="fas fa-star"></i>', floor($owner['average_rating'])) ?>
                                                    <?= ($owner['average_rating'] - floor($owner['average_rating']) >= 0.5) ? '<i class="fas fa-star-half-alt"></i>' : '' ?>
                                                    <span class="text-muted small ms-1">(<?= $owner['average_rating'] ?>)</span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($owner['verified']): ?>
                                                    <span class="badge bg-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>View Profile</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-envelope me-2"></i>Message</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-ban me-2"></i>Suspend</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tenants Tab -->
            <div class="tab-pane fade" id="tenants" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Tenants</h5>
                        <div class="d-flex">
                            <select class="form-select form-select-sm me-2" style="width: 150px;" id="tenantSort">
                                <option value="newest">Newest</option>
                                <option value="active">Active Leases</option>
                                <option value="bookings">Most Bookings</option>
                                <option value="rating">Best Rating</option>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-filter me-1"></i>Filters
                            </button>
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;"></th>
                                        <th>Tenant</th>
                                        <th>Current Lease</th>
                                        <th>Bookings</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tenants as $tenant): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= $tenant['profile_image'] ?: 'https://randomuser.me/api/portraits/men/'.rand(1,100).'.jpg' ?>" 
                                                     class="user-avatar" alt="<?= htmlspecialchars($tenant['full_name']) ?>">
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($tenant['full_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($tenant['email']) ?></div>
                                                <?php if ($tenant['booking_count'] > 5): ?>
                                                    <span class="badge badge-top-rated mt-1">Frequent Renter</span>
                                                <?php elseif ($tenant['created_at'] > date('Y-m-d H:i:s', strtotime('-30 days'))): ?>
                                                    <span class="badge badge-new mt-1">New</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tenant['current_lease']): ?>
                                                    <div class="fw-bold">$<?= number_format($tenant['current_lease']['monthly_rent'], 2) ?>/mo</div>
                                                    <div class="small text-muted">
                                                        Until <?= date('M j, Y', strtotime($tenant['current_lease']['end_date'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No active lease</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $tenant['booking_count'] ?> bookings</td>
                                            <td>
                                                <?php if ($tenant['average_rating'] > 0): ?>
                                                    <div class="rating-stars">
                                                        <?= str_repeat('<i class="fas fa-star"></i>', floor($tenant['average_rating'])) ?>
                                                        <?= ($tenant['average_rating'] - floor($tenant['average_rating'])) >= 0.5 ? '<i class="fas fa-star-half-alt"></i>' : '' ?>
                                                        <span class="text-muted small ms-1">(<?= $tenant['average_rating'] ?>)</span>
                                                    </div>
                                                    <div class="small text-muted"><?= $tenant['reviews_count'] ?> reviews</div>
                                                <?php else: ?>
                                                    <span class="text-muted">No ratings</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tenant['current_lease']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>View Profile</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-envelope me-2"></i>Message</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-ban me-2"></i>Suspend</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple search functionality
        document.getElementById('userSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const activeTab = document.querySelector('.tab-pane.active');
            
            activeTab.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Sort functionality for owners
        document.getElementById('ownerSort').addEventListener('change', function(e) {
            // In a real implementation, this would reload the page with new sorting
            // or make an AJAX request to get sorted data
            alert('Sorting by ' + e.target.value + ' would be implemented here');
        });
        
        // Sort functionality for tenants
        document.getElementById('tenantSort').addEventListener('change', function(e) {
            // In a real implementation, this would reload the page with new sorting
            // or make an AJAX request to get sorted data
            alert('Sorting by ' + e.target.value + ' would be implemented here');
        });
    </script>
</body>
</html>