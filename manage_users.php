<?php
$pageTitle = "Manage Users";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Get session messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Build user query with filters
$userQuery = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $userQuery .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($role) {
    $userQuery .= " AND role = '$role'";
}
if ($status) {
    $userQuery .= " AND status = '$status'";
}
$userQuery .= " ORDER BY created_at DESC";
$users = $conn->query($userQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Users</h1>
        <a href="add_user.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New User
        </a>
    </div>

    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Search and Filter Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or email" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="owner" <?php echo $role === 'owner' ? 'selected' : ''; ?>>Owner</option>
                        <option value="tenant" <?php echo $role === 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Generation -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="generate_user_report.php" class="row g-3">
                <div class="col-md-2">
                    <select class="form-select" name="report_role">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="owner">Owner</option>
                        <option value="tenant">Tenant</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="report_status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_from" placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_to" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="sort_by">
                        <option value="created_at">Sort by Joined</option>
                        <option value="full_name">Sort by Name</option>
                        <option value="email">Sort by Email</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <select class="form-select" name="sort_order">
                        <option value="DESC">Descending</option>
                        <option value="ASC">Ascending</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <div class="btn-group w-100">
                        <button type="submit" name="format" value="csv" class="btn btn-success">CSV</button>
                        <button type="submit" name="format" value="pdf" class="btn btn-danger">PDF</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Users</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover">
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
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="view_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-danger delete-user" 
                                        data-id="<?php echo $user['user_id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" method="POST" action="delete_user.php">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.6/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.6/css/dataTables.bootstrap5.min.css">

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#usersTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[6, 'desc']]
    });

    // Delete user modal
    $('.delete-user').click(function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        $('#deleteUserName').text(userName);
        $('#deleteUserId').val(userId);
        $('#deleteUserModal').modal('show');
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>