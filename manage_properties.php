<?php
include 'db_connection.php'; 

// Fetch all properties
$query = "SELECT * FROM properties ORDER BY created_at DESC";
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

        .btn-success { background-color: var(--primary); }
        .btn-danger { background-color: crimson; }
        .btn-warning { background-color: var(--secondary); color: black; }
        .btn-info { background-color: var(--accent); }

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
            <th>Title</th>
            <th>Owner ID</th>
            <th>Price</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= $row['owner_id'] ?></td>
                <td>$<?= number_format($row['price_per_month'], 2) ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    <button class="btn btn-success" onclick="approveProperty(<?= $row['property_id'] ?>)">Approve</button>
                    <button class="btn btn-danger" onclick="rejectProperty(<?= $row['property_id'] ?>)">Reject</button>
                    
                    <button class="btn btn-info" onclick="viewReport(<?= $row['property_id'] ?>)">View Report</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    function approveProperty(id) {
        if (confirm("Are you sure you want to approve this property?")) {
            window.location.href = 'property_actions.php?action=approve&id=' + id;
        }
    }

    function rejectProperty(id) {
        if (confirm("Are you sure you want to reject this property?")) {
            window.location.href = 'property_actions.php?action=reject&id=' + id;
        }
    }

    function editProperty(id) {
        window.location.href = 'edit_property.php?property_id=' + id;
    }

    function viewReport(id) {
        window.location.href = 'property_report.php?property_id=' + id;
    }
</script>

</body>
</html>
