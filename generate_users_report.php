<?php
session_start();
require_once 'db_connection.php';

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

    // Display HTML report
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Overall Users Report</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            body {
                background: #1e3c2b;
                color: #ffffff;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .glass {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 12px;
                padding: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            .table {
                width: 100%;
                max-width: 1200px;
                border-collapse: collapse;
                margin-bottom: 12px;
                font-size: 12px;
            }
            .table th, .table td {
                border: 1px solid rgba(255, 255, 255, 0.15);
                padding: 4px;
                text-align: left;
            }
            .table th {
                background: linear-gradient(135deg, #1e3c2b, #2a7f62);
                color: #ffffff;
            }
            h1 {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }
            p {
                font-size: 0.875rem;
                margin-bottom: 8px;
            }
            .container {
                max-width: 1200px;
                padding: 16px;
            }
            .spinner {
                display: none;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #2a7f62;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                animation: spin 1s linear infinite;
                margin: 0 auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            @media print {
                body {
                    background: #ffffff;
                    color: #000000;
                    font-size: 10pt;
                }
                .glass {
                    background: none;
                    border: none;
                    backdrop-filter: none;
                    padding: 0;
                }
                .no-print {
                    display: none;
                }
                .table {
                    max-width: 100%;
                    border-collapse: collapse;
                }
                .table th, .table td {
                    border: 1px solid #000000;
                    padding: 6px;
                }
                .table th {
                    background-color: #f0f0f0;
                    color: #000000;
                }
                .container {
                    margin: 0;
                    padding: 10mm;
                }
                h1 {
                    font-size: 14pt;
                    margin-bottom: 10mm;
                }
                p {
                    font-size: 10pt;
                    margin-bottom: 5mm;
                }
            }
        </style>
    </head>
    <body>
        <div class="container mx-auto">
            <div class="glass">
                <h1 class="font-bold text-center">Overall Users Report</h1>
                <p class="text-center">Generated on <?php echo date('Y-m-d'); ?></p>

                <?php if (empty($users)): ?>
                    <p class="text-center">No users found for the selected filters.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo substr($user['user_id'], 0, 8); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td><?php echo ucfirst($user['status']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="text-center no-print">
                    <button id="printButton" onclick="printReport()" class="btn btn-success px-3 py-1 text-sm bg-[#2a7f62] hover:bg-[#226b4f] rounded">
                        <i class="fas fa-file-pdf"></i> Print/Save as PDF
                    </button>
                    <div id="spinner" class="spinner"></div>
                    <p id="redirectMessage" class="text-sm mt-2 hidden">Preparing PDF, redirecting to Manage Users...</p>
                </div>
            </div>
        </div>
        <script>
            function printReport() {
                document.getElementById('printButton').classList.add('hidden');
                document.getElementById('spinner').style.display = 'block';
                document.getElementById('redirectMessage').classList.remove('hidden');
                window.print();
                setTimeout(() => {
                    window.location.href = 'manage_users.php';
                }, 2000);
            }
        </script>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error generating report: " . $e->getMessage();
    header("Location: manage_users.php");
    exit;
}

$conn->close();
?>