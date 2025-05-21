<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$owner_id = $_SESSION['user_id'];
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch properties with main photo
$query = "SELECT p.*, pp.photo_url 
          FROM properties p 
          LEFT JOIN property_photos pp ON p.property_id = pp.property_id 
          WHERE p.owner_id = ? 
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - JIGJIGAHOMES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success { background: rgba(76, 175, 80, 0.3); }
        .error { background: rgba(244, 67, 54, 0.3); }

        .property-table {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            color: #FFD700;
            font-weight: bold;
        }

        td img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #ffffff;
            margin-right: 5px;
        }

        .edit-btn { background: #FFD700; color: #1a2a44; }
        .delete-btn { background: #FF6347; }
       
    .approve-btn { background: #28a745; }
    .reject-btn { background: #dc3545; }


        .add-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #FFD700;
            color: #1a2a44;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .modal-content.glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .modal-header, .modal-footer {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .modal-title {
            color: #FFD700;
        }

        .btn-close {
            filter: invert(1);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff;
        }

        .btn-danger {
            background: #FF6347;
            border: none;
        }

        .btn-danger:hover {
            background: #e5533d;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; }
            table { font-size: 0.9rem; }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { position: relative; }
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 24px;
                color: #FFD700;
            }
            table { font-size: 0.8rem; }
            .action-btn { padding: 6px 10px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>JIGJIGAHOMES</h2>
        <a href="owner_dashboard.php">Dashboard</a>
        <a href="my_properties.php" class="active">My Properties</a>
        <a href="owner_manage_leases.php">Manage Leases</a>
        <a href="owner_view_payments.php">View Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
            <h1>My Properties</h1>
            <div class="profile-dropdown">
                <button class="profile-btn" aria-label="Profile menu">Profile</button>
                <div class="dropdown-content">
                    <a href="profile.php">Edit Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <a href="owner_add_property.php" class="add-btn">Add New Property</a>
        <div class="property-table">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Address</th>
                        <th>Price/Month</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
           
<tbody>
    <?php foreach ($properties as $property): ?>
        <tr>
            <td><img src="<?php echo htmlspecialchars($property['photo_url'] ?? 'Uploads/default.jpg'); ?>" alt="Property Image"></td>
            <td><?php echo htmlspecialchars($property['title']); ?></td>
            <td><?php echo htmlspecialchars($property['address_detail']); ?></td>
            <td>ETB <?php echo number_format($property['price_per_month'], 2); ?></td>
            <td><?php echo htmlspecialchars($property['status']); ?></td>
            <td>
                <a href="owner_edit_property.php?id=<?php echo htmlspecialchars($property['property_id']); ?>" class="action-btn edit-btn">Edit</a>
                <button class="action-btn delete-btn" data-bs-toggle="modal" data-bs-target="#deletePropertyModal" 
                        data-property-id="<?php echo htmlspecialchars($property['property_id']); ?>" 
                        data-property-title="<?php echo htmlspecialchars($property['title']); ?>">Delete</button>
                <?php if ($property['status'] === 'reserved'): ?>
                    <form action="owner_manage_property_status.php" method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property['property_id']); ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="action-btn approve-btn">Approve</button>
                    </form>
                    <form action="owner_manage_property_status.php" method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property['property_id']); ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="action-btn reject-btn">Reject</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </div>

        <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deletePropertyModal" tabindex="-1" aria-labelledby="deletePropertyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content glass">
                    <div class="modal-header">
                        <h5 class="modal-title text-sm" id="deletePropertyModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-sm">
                        Delete <strong id="deletePropertyTitle"></strong>? This cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary text-sm" data-bs-dismiss="modal">Cancel</button>
                        <form id="deletePropertyForm" method="POST" action="owner_delete_property.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="confirm_delete" value="yes">
                            <input type="hidden" name="property_id" id="deletePropertyId">
                            <button type="submit" class="btn btn-danger text-sm">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // Handle delete button click to populate modal
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const propertyId = this.getAttribute('data-property-id');
                const propertyTitle = this.getAttribute('data-property-title');
                document.getElementById('deletePropertyId').value = propertyId;
                document.getElementById('deletePropertyTitle').textContent = propertyTitle;
            });
        });
    </script>
</body>
</html>