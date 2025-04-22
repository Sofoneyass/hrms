<?php
include 'auth_session.php';
require 'db_connection.php';

$query = "SELECT user_id, full_name, email, phone, role, created_at FROM users";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Users</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .container { padding: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #343a40; color: white; }
        a.action-btn { margin-right: 10px; text-decoration: none; font-weight: bold; }
        .edit { color: green; }
        .delete { color: red; }
        .activity { color: #007bff; }
        h2 { color: #333; }
        select { padding: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Users</h2>
        <table>
            <thead>
                <tr>
                    <th>User ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Created At</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['user_id'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td>
                        <form action="update_user_role.php" method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="tenant" <?= $row['role'] === 'tenant' ? 'selected' : '' ?>>Tenant</option>
                                <option value="owner" <?= $row['role'] === 'owner' ? 'selected' : '' ?>>Owner</option>
                                <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <a class="action-btn edit" href="edit_user.php?id=<?= $row['user_id'] ?>">Edit</a>
                        <a class="action-btn delete" href="delete_user.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        <a class="action-btn activity" href="user_activity.php?id=<?= $row['user_id'] ?>">Activity</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <br>
        <a href="overall_users_report.php" style="font-weight:bold;">📊 Generate User Reports</a>
    </div>
</body>
</html>
