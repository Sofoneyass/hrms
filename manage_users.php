<?php
include 'db_connection.php';

$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        :root {
            --primary: #10B981;
            --secondary: #FBBF24;
            --accent: #06B6D4;
            --dark: #1F2937;
            --darker: #111827;
            --text-light: rgba(255,255,255,0.9);
            --text-muted: rgba(255,255,255,0.7);
            --card-bg: rgba(31, 41, 55, 0.8);
            --card-border: rgba(255, 255, 255, 0.15);
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: var(--darker);
            color: var(--text-light);
        }

        .container {
            padding: 2rem;
        }

        h1 {
            text-align: center;
            color: var(--primary);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            border-radius: 12px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: var(--dark);
        }

        tr:nth-child(even) {
            background-color: rgba(255,255,255,0.02);
        }

        .btn {
            padding: 6px 10px;
            margin: 2px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: white;
        }

        .btn-edit { background-color: var(--accent); }
        .btn-delete { background-color: crimson; }
        .btn-role { background-color: var(--secondary); color: black; }

        .btn:hover {
            opacity: 0.85;
        }

        select {
            padding: 4px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Manage Users</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($user = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $user['user_id'] ?></td>
                <td><?= htmlspecialchars($user['full_name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td>
                    <select onchange="changeRole(<?= $user['user_id'] ?>, this.value)">
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="owner" <?= $user['role'] == 'owner' ? 'selected' : '' ?>>Owner</option>
                        <option value="tenant" <?= $user['role'] == 'tenant' ? 'selected' : '' ?>>Tenant</option>
                    </select>
                </td>
                <td><?= ucfirst($user['status']) ?></td>
                <td><?= $user['created_at'] ?></td>
                <td>
                    <button class="btn btn-edit" onclick="editUser(<?= $user['user_id'] ?>)">Edit</button>
                    <button class="btn btn-delete" onclick="deleteUser(<?= $user['user_id'] ?>)">Delete</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    function editUser(id) {
        window.location.href = 'edit_user.php?user_id=' + id;
    }

    function deleteUser(id) {
        if (confirm("Are you sure you want to delete this user?")) {
            window.location.href = 'delete_user.php?user_id=' + id;
        }
    }

    function changeRole(userId, newRole) {
        if (confirm("Are you sure you want to change the user role to " + newRole + "?")) {
            window.location.href = 'update_user_role.php?user_id=' + userId + '&role=' + newRole;
        }
    }
</script>

</body>
</html>
