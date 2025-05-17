<?php
session_start();
require 'db_connection.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");




// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Validate role
if (!in_array($role, ['admin', 'owner', 'tenant'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Fetch user details with prepared statement
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Profile - Jigjiga Homes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e3c2b 0%, #2a7f62 100%);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .profile-container {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h3 {
            font-size: 24px;
            color: #f0c14b;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            margin-bottom: 10px;
        }

        .sidebar ul li a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 16px;
            display: block;
            padding: 12px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: #f0c14b;
            color: #1e3c2b;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .profile-card {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .profile-avatar {
            position: relative;
            margin-bottom: 20px;
        }

        .profile-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0c14b;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #f0c14b;
            color: #1e3c2b;
        }

        .btn-danger {
            background: #ff4d4d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        table th {
            background: rgba(0, 0, 0, 0.2);
            color: #f0c14b;
        }

        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                padding: 20px;
            }
        }
</style>
</head>
<body>
    <div class="profile-container">
        <div class="sidebar">
            <h3>Jigjiga Homes</h3>
            <ul>
                <li><a href="#" class="sidebar-link active" data-section="profile"><i class="fas fa-user"></i> Profile</a></li>
                
                <?php if ($role === 'admin'): ?>
                    <li><a href="#" class="sidebar-link" data-section="users"><i class="fas fa-users"></i> Manage Users</a></li>
                    <li><a href="#" class="sidebar-link" data-section="properties"><i class="fas fa-home"></i> All Properties</a></li>
                    <li><a href="#" class="sidebar-link" data-section="bookings"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <?php elseif ($role === 'owner'): ?>
                    <li><a href="#" class="sidebar-link" data-section="properties"><i class="fas fa-home"></i> My Properties</a></li>
                    <li><a href="#" class="sidebar-link" data-section="bookings"><i class="fas fa-calendar-check"></i> Property Bookings</a></li>
                <?php elseif ($role === 'tenant'): ?>
                    <li><a href="#" class="sidebar-link" data-section="bookings"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                    <li><a href="#" class="sidebar-link" data-section="favorites"><i class="fas fa-heart"></i> Favorites</a></li>
                    
                    
                <?php endif; ?>
                
                <li><a href="logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Profile Section -->
            <div class="content-section active" id="profile">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <img src="<?php echo htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>" alt="Profile Image">
                    </div>
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst($role); ?></p>
                    <button class="btn btn-primary" onclick="openEditModal()">Edit Profile</button>
                </div>
            </div>

            <!-- Admin Sections -->
            <?php if ($role === 'admin'): ?>
                <div class="content-section" id="users">
                    <h2>Manage Users</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT user_id, full_name, email, role FROM users");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo ucfirst($row['role']); ?></td>
                                    <td>
                                        <button class="btn btn-primary" onclick="editUser(<?php echo $row['user_id']; ?>)">Edit</button>
                                        <button class="btn btn-danger" onclick="confirmDelete('user', <?php echo $row['user_id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php $stmt->close(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Owner/Tenant Sections -->
            <?php if ($role === 'owner' || $role === 'tenant'): ?>
                <!-- Properties Section -->
                <div class="content-section" id="properties">
                    <h2><?php echo $role === 'owner' ? 'My Properties' : 'Available Properties'; ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($role === 'owner') {
                                $stmt = $conn->prepare("SELECT property_id, title, location, price_per_month, status FROM properties WHERE owner_id = ?");
                                $stmt->bind_param("i", $user_id);
                            } else {
                                $stmt = $conn->prepare("SELECT property_id, title, location, price_per_month, status FROM properties WHERE status = 'available'");
                            }
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td>$<?php echo number_format($row['price_per_month'], 2); ?></td>
                                    <td><?php echo ucfirst($row['status']); ?></td>
                                    <td>
                                        <?php if ($role === 'owner'): ?>
                                            <button class="btn btn-primary" onclick="editProperty(<?php echo $row['property_id']; ?>)">Edit</button>
                                            <button class="btn btn-danger" onclick="confirmDelete('property', <?php echo $row['property_id']; ?>)">Delete</button>
                                        <?php else: ?>
                                            <button class="btn btn-primary" onclick="viewProperty(<?php echo $row['property_id']; ?>)">View</button>
                                            <button class="btn btn-primary" onclick="addFavorite(<?php echo $row['property_id']; ?>)">Favorite</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                               
                            <?php endwhile; ?>
                            <?php $stmt->close(); ?>
                        </tbody>
                    </table>
                    <?php if ($role === 'owner'): ?>
                        <button class="btn btn-primary" style="margin-top: 20px;" onclick="openAddPropertyModal()">Add Property</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- All roles have bookings section with different views -->
            <div class="content-section" id="bookings">
                <h2>
                    <?php echo $role === 'admin' ? 'All Bookings' : 
                          ($role === 'owner' ? 'Property Bookings' : 'My Bookings'); ?>
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($role === 'admin') {
                            $stmt = $conn->prepare("SELECT b.booking_id, p.title, b.start_date, b.end_date, b.status 
                                                   FROM bookings b JOIN properties p ON b.property_id = p.property_id");
                        } elseif ($role === 'owner') {
                            $stmt = $conn->prepare("SELECT b.booking_id, p.title, b.start_date, b.end_date, b.status 
                                                   FROM bookings b JOIN properties p ON b.property_id = p.property_id 
                                                   WHERE p.owner_id = ?");
                            $stmt->bind_param("i", $user_id);
                        } else {
                            $stmt = $conn->prepare("SELECT b.booking_id, p.title, b.start_date, b.end_date, b.status 
                                                   FROM bookings b JOIN properties p ON b.property_id = p.property_id 
                                                   WHERE b.tenant_id = ?");
                            $stmt->bind_param("i", $user_id);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['start_date'])); ?> - <?php echo date('M j, Y', strtotime($row['end_date'])); ?></td>
                                <td><?php echo ucfirst($row['status']); ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="viewBooking(<?php echo $row['booking_id']; ?>)">Details</button>
                                    <?php if ($role === 'tenant' && $row['status'] === 'pending'): ?>
                                        <button class="btn btn-danger" onclick="cancelBooking(<?php echo $row['booking_id']; ?>)">Cancel</button>
                                    <?php elseif ($role === 'owner' && $row['status'] === 'pending'): ?>
                                        <button class="btn btn-primary" onclick="approveBooking(<?php echo $row['booking_id']; ?>)">Approve</button>
                                        <button class="btn btn-danger" onclick="rejectBooking(<?php echo $row['booking_id']; ?>)">Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php $stmt->close(); ?>
                    </tbody>
                </table>
            </div>

            <!-- Tenant-specific sections -->
            <?php if ($role === 'tenant'): ?>
                <div class="content-section" id="favorites">
                    <h2>My Favorites</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT p.property_id, p.title, p.location, p.price_per_month 
                                                     FROM properties p JOIN favorites f ON p.property_id = f.property_id 
                                                     WHERE f.tenant_id = ?");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td>$<?php echo number_format($row['price_per_month'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-primary" onclick="viewProperty(<?php echo $row['property_id']; ?>)">View</button>
                                        <button class="btn btn-danger" onclick="removeFavorite(<?php echo $row['property_id']; ?>)">Remove</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php $stmt->close(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="content-section" id="maintenance">
                    <h2>Maintenance Requests</h2>
                    <button class="btn btn-primary" onclick="openMaintenanceModal()">New Request</button>
                    <table>
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                           $stmt = $conn->prepare("
                           SELECT 
                               m.request_id, 
                               p.title, 
                               m.description, 
                               m.status, 
                               m.request_date 
                           FROM maintenance_requests m
                           JOIN properties p ON m.property_id = p.property_id
                           JOIN leases l ON m.lease_id = l.lease_id
                           WHERE l.tenant_id = ?
                       ");
                       
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo ucfirst($row['status']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['request_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php $stmt->close(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Edit Profile</h3>
            <form id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" pattern="\+?[0-9]{10,15}">
                </div>
                
                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" name="avatar" accept="image/*">
                </div>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        // Navigation
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                
                this.classList.add('active');
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
            });
        });

        // Modal functions
        function openEditModal() {
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Form submission with CSRF protection
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to update profile'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });

        // Other action functions would be implemented similarly with CSRF protection
        function confirmDelete(type, id) {
            if (confirm(`Are you sure you want to delete this ${type}?`)) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: type,
                        id: id,
                        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully`);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || `Failed to delete ${type}`));
                    }
                });
            }
        }
    </script>
</body>
</html>