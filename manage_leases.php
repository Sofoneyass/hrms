<?php
include 'db_connection.php';

// Fetch all leases with property title and tenant name
$query = "
SELECT leases.*, properties.title AS property_title, users.full_name AS tenant_name
FROM leases
JOIN properties ON leases.property_id = properties.property_id
JOIN users ON leases.tenant_id = users.user_id
ORDER BY leases.created_at DESC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Leases</title>
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
            color: var(--text-light);
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
        .btn:hover {
            opacity: 0.85;
        }
    </style>
</head>
<body>

<div class="container">
   

    <table>
        <thead>
        <tr>
            <th>Lease ID</th>
            <th>Property</th>
            <th>Tenant</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($lease = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $lease['lease_id'] ?></td>
                <td><?= htmlspecialchars($lease['property_title']) ?></td>
                <td><?= htmlspecialchars($lease['tenant_name']) ?></td>
                <td>$<?= number_format($lease['monthly_rent'], 2) ?></td>
                <td><?= ucfirst($lease['status']) ?></td>
                <td><?= $lease['start_date'] ?></td>
                <td><?= $lease['end_date'] ?></td>
                <td><?= $lease['created_at'] ?></td>
                <td>
                    <a class="btn btn-edit" href="edit_lease.php?lease_id=<?= $lease['lease_id'] ?>">Edit</a>
                    <a class="btn btn-delete" href="terminate_lease.php?lease_id=<?= $lease['lease_id'] ?>" onclick="return confirm('Are you sure you want to terminate this lease?')">Terminate</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
