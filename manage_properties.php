<?php
include 'auth_session.php';
require 'db_connection.php';

// Query to get available properties
$available_query = "SELECT property_id, title, location, price_per_month, status, created_at FROM properties WHERE status = 'available'";
$available_result = $conn->query($available_query);

// Query to get reserved properties
$reserved_query = "SELECT property_id, title, location, price_per_month, status, created_at FROM properties WHERE status = 'reserved'";
$reserved_result = $conn->query($reserved_query);

// Handle approve/reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $property_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        // Update the property status to 'reserved' (if it's not already reserved)
        $update_query = "UPDATE properties SET status = 'reserved' WHERE property_id = $property_id AND status != 'reserved'";
        if ($conn->query($update_query)) {
            echo "<script>alert('Property reserved successfully'); window.location.href = 'manage_properties.php';</script>";
        } else {
            echo "<script>alert('Error reserving property');</script>";
        }
    } elseif ($action == 'reject') {
        // Update the property status to 'available' (if it's reserved)
        $update_query = "UPDATE properties SET status = 'available' WHERE property_id = $property_id AND status = 'reserved'";
        if ($conn->query($update_query)) {
            echo "<script>alert('Property marked as available'); window.location.href = 'manage_properties.php';</script>";
        } else {
            echo "<script>alert('Error updating property status');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Properties</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .container { padding: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #343a40; color: white; }
        a.delete-btn { color: red; text-decoration: none; font-weight: bold; }
        .action-btns a { margin-right: 10px; color: #28a745; text-decoration: none; font-weight: bold; }
        .action-btns a.reject { color: #dc3545; }
        h2 { color: #333; }
        h3 { color: #007bff; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Properties</h2>

        <!-- Available Properties Table -->
        <h3>Available Properties</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Title</th><th>Location</th><th>Price</th><th>Status</th><th>Created</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $available_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['property_id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td>$<?= number_format($row['price_per_month'], 2) ?></td>
                    <td><?= $row['status'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td><a class="delete-btn" href="delete_property.php?id=<?= $row['property_id'] ?>" onclick="return confirm('Delete this property?')">Delete</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Reserved Properties Table -->
        <h3>Reserved Properties</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Title</th><th>Location</th><th>Price</th><th>Status</th><th>Created</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $reserved_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['property_id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td>$<?= number_format($row['price_per_month'], 2) ?></td>
                    <td><?= $row['status'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td class="action-btns">
                        <!-- Approve and Reject Buttons -->
                        <a href="manage_properties.php?action=approve&id=<?= $row['property_id'] ?>" onclick="return confirm('Approve this reservation?')" class="approve">Approve</a>
                        <a href="manage_properties.php?action=reject&id=<?= $row['property_id'] ?>" onclick="return confirm('Reject this reservation?')" class="reject">Reject</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
