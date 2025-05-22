<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

$owner_id = $_SESSION['user_id'];

// Handle lease actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['lease_id'])) {
    $lease_id = filter_var($_POST['lease_id'], FILTER_VALIDATE_REGEXP, [
        'options' => ['regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i']
    ]);
    $action = $_POST['action'];
    if ($lease_id && in_array($action, ['approve', 'reject', 'terminate'])) {
        if ($action === 'approve') {
            $query = $conn->prepare("UPDATE leases SET status = 'active' WHERE lease_id = ? AND status = 'pending'");
        } elseif ($action === 'reject') {
            $query = $conn->prepare("UPDATE leases SET status = 'rejected' WHERE lease_id = ? AND status = 'pending'");
        } else {
            $query = $conn->prepare("UPDATE leases SET status = 'terminated' WHERE lease_id = ? AND status = 'active'");
        }
        $query->bind_param("s", $lease_id);
        $query->execute();
        $query->close();
    }
    header("Location: owner_manage_leases.php");
    exit;
}

// Fetch leases
$status_filter = isset($_GET['status']) && in_array($_GET['status'], ['all', 'active', 'pending', 'expired', 'terminated', 'rejected']) ? $_GET['status'] : 'all';
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_clause = "p.owner_id = ?";
$types = "s";
$params = [$owner_id];

if ($status_filter !== 'all') {
    $where_clause .= " AND l.status = ?";
    $types .= "s";
    $params[] = $status_filter;
}

$query = $conn->prepare("
    SELECT l.lease_id, l.tenant_id, l.property_id, l.start_date, l.end_date, l.monthly_rent, l.status, 
           p.title, u.full_name
    FROM leases l
    JOIN properties p ON l.property_id = p.property_id
    JOIN users u ON l.tenant_id = u.user_id
    WHERE $where_clause
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
");
$types .= "ii";
$params[] = $per_page;
$params[] = $offset;

if (!$query) {
    die("Query preparation failed: " . $conn->error);
}

$query->bind_param($types, ...$params);
if (!$query->execute()) {
    die("Query execution failed: " . $query->error);
}
$leases = $query->get_result()->fetch_all(MYSQLI_ASSOC);
$query->close();

// Count total leases for pagination
$count_where_clause = $where_clause;
$count_types = substr($types, 0, -2); // Remove "ii" for LIMIT/OFFSET
$count_params = array_slice($params, 0, -2);

$count_query = $conn->prepare("SELECT COUNT(*) as total FROM leases l JOIN properties p ON l.property_id = p.property_id WHERE $count_where_clause");
if (!$count_query) {
    die("Count query preparation failed: " . $conn->error);
}
$count_query->bind_param($count_types, ...$count_params);
if (!$count_query->execute()) {
    die("Count query execution failed: " . $count_query->error);
}
$total_leases = $count_query->get_result()->fetch_assoc()['total'];
$count_query->close();
$total_pages = ceil($total_leases / $per_page);

// Generate CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leases | JIGJIGAHOMES</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a2a44 0%, #2a4066 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar h2 {
            color: #FFD700;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: #ffffff;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #FFD700;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            background: none;
            border: none;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 5px;
            min-width: 150px;
            z-index: 1;
        }

        .dropdown-content a {
            color: #ffffff;
            padding: 12px 16px;
            display: block;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background: rgba(255, 215, 0, 0.2);
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }

        .filters select {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff;
            padding: 8px;
            border-radius: 5px;
            font-size: 14px;
        }

        .filters select:focus {
            outline: 2px solid #FFD700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
            font-size: 14px;
            text-transform: uppercase;
        }

        td {
            color: #ffffff;
            font-size: 14px;
        }

        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .status.active { background: #4CAF50; }
        .status.pending { background: #FFD700; color: #1a2a44; }
        .status.expired { background: #FF6347; }
        .status.terminated { background: #FF6347; }
        .status.rejected { background: #808080; }

        .action-btn {
            background: #FFD700;
            border: none;
            color: #1a2a44;
            padding: 6px 12px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }

        .action-btn:hover {
            background: #e6c200;
        }

        .action-btn.reject, .action-btn.terminate {
            background: #FF6347;
            color: #ffffff;
        }

        .action-btn.reject:hover, .action-btn.terminate:hover {
            background: #e5533d;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            color: #ffffff;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            transition: background 0.3s;
        }

        .pagination a:hover, .pagination a.active {
            background: #FFD700;
            color: #1a2a44;
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; }
            table { font-size: 12px; }
            th, td { padding: 8px; }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content { margin-left: 0; }
            .header { position: relative; }
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 24px;
                color: #FFD700;
            }
            .filters { flex-wrap: wrap; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>JIGJIGAHOMES</h2>
        <a href="owner_dashboard.php">Dashboard</a>
        <a href="my_properties.php">My Properties</a>
        <a href="owner_manage_leases.php" class="active">Manage Leases</a>
        <a href="messages.php">Messages</a>
        <a href="view_payments.php">View Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
            <h1>Manage Leases</h1>
            <div class="profile-dropdown">
                <button class="profile-btn" aria-label="Profile menu">Profile</button>
                <div class="dropdown-content">
                    <a href="profile.php">Edit Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="filters">
                <label for="statusFilter" style="color: #FFD700;">Filter by Status:</label>
                <select id="statusFilter" onchange="window.location.href='owner_manage_leases.php?status='+this.value">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="terminated" <?php echo $status_filter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Lease ID</th>
                        <th>Property</th>
                        <th>Tenant</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Monthly Rent (ETB)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leases)): ?>
                        <tr><td colspan="8" style="text-align: center;">No leases found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leases as $lease): ?>
                            <tr>
                                <td><?php echo substr($lease['lease_id'], 0, 8); ?>...</td>
                                <td><?php echo htmlspecialchars($lease['title']); ?></td>
                                <td><?php echo htmlspecialchars($lease['full_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($lease['start_date'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($lease['end_date'])); ?></td>
                                <td><?php echo number_format($lease['monthly_rent'], 2); ?></td>
                                <td><span class="status <?php echo strtolower($lease['status']); ?>"><?php echo $lease['status']; ?></span></td>
                                <td>
                                    <?php if ($lease['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="lease_id" value="<?php echo $lease['lease_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <button type="submit" class="action-btn">Approve</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="lease_id" value="<?php echo $lease['lease_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <button type="submit" class="action-btn reject">Reject</button>
                                        </form>
                                    <?php elseif ($lease['status'] === 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="lease_id" value="<?php echo $lease['lease_id']; ?>">
                                            <input type="hidden" name="action" value="terminate">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <button type="submit" class="action-btn terminate">Terminate</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>