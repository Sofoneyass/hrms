<?php
$pageTitle = "Manage Users";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get session messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Initialize filter parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'role' => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
];

// Build query
$whereClauses = [];
$params = [];
$paramTypes = '';

if ($filters['search']) {
    $whereClauses[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = '%' . $filters['search'] . '%';
    $params[] = '%' . $filters['search'] . '%';
    $paramTypes .= 'ss';
}
if ($filters['role']) {
    $whereClauses[] = "role = ?";
    $params[] = $filters['role'];
    $paramTypes .= 's';
}
if ($filters['status']) {
    $whereClauses[] = "status = ?";
    $params[] = $filters['status'];
    $paramTypes .= 's';
}

$whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    // Get users
    $userQuery = "
        SELECT * FROM users
        $whereSql
        ORDER BY created_at DESC
    ";
    $stmt = $conn->prepare($userQuery);
    if ($paramTypes) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    $users = [];
}
?>

<style>
    .glass { 
        background: rgba(255, 255, 255, 0.1); 
        backdrop-filter: blur(10px); 
        border: 1px solid rgba(255, 255, 255, 0.15); 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); 
    }
    .table th, .table td { padding: 8px; font-size: 0.9rem; }
    .btn-sm { padding: 4px 8px; }
    input, select { transition: all 0.2s; }
    input:focus, select:focus { outline: none; border-color: #f0c14b; box-shadow: 0 0 0 2px rgba(240, 193, 75, 0.2); }
    .spinner { 
        display: none; 
        border: 3px solid #f3f3f3; 
        border-top: 3px solid #2a7f62; 
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
        <h1 class="text-2xl font-bold">Users</h1>
        <div class="flex space-x-2">
            <button id="toggleFilters" class="btn btn-secondary text-sm"><i class="fas fa-filter"></i> Filters</button>
            <a href="add_user.php" class="btn btn-primary text-sm"><i class="fas fa-plus"></i> Add</a>
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
                    <label class="block text-xs font-medium">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                           class="w-full p-1 rounded bg-gray-200 text-black border border-gray-400 text-sm focus:border-[#f0c14b] focus:ring focus:ring-[#f0c14b] focus:ring-opacity-50" 
                           placeholder="Name or Email" autofocus>
                </div>
                <div>
                    <label class="block text-xs font-medium">Role</label>
                    <select name="role" class="w-full p-1 rounded bg-gray-200 text-black border border-gray-400 text-sm focus:border-[#f0c14b] focus:ring focus:ring-[#f0c14b] focus:ring-opacity-50">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $filters['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="owner" <?php echo $filters['role'] === 'owner' ? 'selected' : ''; ?>>Owner</option>
                        <option value="tenant" <?php echo $filters['role'] === 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium">Status</label>
                    <select name="status" class="w-full p-1 rounded bg-gray-200 text-black border border-gray-400 text-sm focus:border-[#f0c14b] focus:ring focus:ring-[#f0c14b] focus:ring-opacity-50">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="sm:col-span-3 flex space-x-2">
                    <button type="submit" class="btn btn-primary w-full text-sm"><i class="fas fa-filter"></i> Apply</button>
                    <button type="button" id="clearFilters" class="btn btn-secondary w-full text-sm"><i class="fas fa-times"></i> Clear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card glass">
        <div class="card-header p-3">
            <h5 class="text-sm font-semibold">All Users (<?php echo count($users); ?>)</h5>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo substr($user['user_id'], 0, 8); ?></td>
                                <td>
                                    <img src="Uploads/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.jpg'); ?>" 
                                         width="30" height="30" class="rounded-circle me-2">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] === 'admin' ? 'danger' : 
                                             ($user['role'] === 'owner' ? 'primary' : 'success'); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['status'] === 'active' ? 'success' : 
                                             ($user['status'] === 'inactive' ? 'secondary' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td class="flex space-x-1">
                                    <a href="view_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger delete-user" 
                                            data-id="<?php echo $user['user_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($user['full_name']); ?>"><i class="fas fa-trash"></i></button>
                                    <button class="btn btn-sm btn-success generate-report" 
                                            data-id="<?php echo $user['user_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($user['full_name']); ?>"><i class="fas fa-file-pdf"></i><span class="spinner"></span></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass">
            <div class="modal-header">
                <h5 class="modal-title text-sm" id="deleteUserModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-sm">
                Delete <strong id="deleteUserName"></strong>? This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary text-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" method="POST" action="delete_user.php">
                    <input type="hidden" name="user_id" id="deleteUserId">
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
                Generate PDF for <strong id="generateReportName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary text-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="generateReportForm" method="POST" action="generate_user_report.php">
                    <input type="hidden" name="user_id" id="generateReportId">
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
        window.location.href = 'manage_users.php';
    });

    // Delete modal
    $('.delete-user').click(function() {
        $('#deleteUserName').text($(this).data('name'));
        $('#deleteUserId').val($(this).data('id'));
        $('#deleteUserModal').modal('show');
    });

    // Single report modal
    $('.generate-report').click(function() {
        $('#generateReportName').text($(this).data('name'));
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
        window.location.href = 'generate_users_report.php?' + $('#filterForm').serialize();
        setTimeout(() => $('#exportAllSpinner').hide(), 1000);
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>