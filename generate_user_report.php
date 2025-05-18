<?php

require_once 'db_connection.php';
require_once 'auth_session.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validate request method and authentication
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: manage_users.php");
    exit;
}

// Get and validate input parameters
$role = isset($_POST['report_role']) ? trim($_POST['report_role']) : '';
$status = isset($_POST['report_status']) ? trim($_POST['report_status']) : '';
$dateFrom = isset($_POST['date_from']) ? trim($_POST['date_from']) : '';
$dateTo = isset($_POST['date_to']) ? trim($_POST['date_to']) : '';
$format = isset($_POST['format']) && in_array($_POST['format'], ['csv', 'pdf']) ? $_POST['format'] : 'csv';
$sortBy = isset($_POST['sort_by']) && in_array($_POST['sort_by'], ['full_name', 'email', 'created_at']) ? $_POST['sort_by'] : 'created_at';
$sortOrder = isset($_POST['sort_order']) && in_array($_POST['sort_order'], ['ASC', 'DESC']) ? $_POST['sort_order'] : 'DESC';

// Validate inputs
$errors = [];
if ($role && !in_array($role, ['admin', 'owner', 'tenant'])) {
    $errors[] = "Invalid role selected.";
}
if ($status && !in_array($status, ['active', 'inactive', 'pending'])) {
    $errors[] = "Invalid status selected.";
}
if ($dateFrom && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateFrom)) {
    $errors[] = "Invalid start date format.";
}
if ($dateTo && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateTo)) {
    $errors[] = "Invalid end date format.";
}
if ($dateFrom && $dateTo && strtotime($dateFrom) > strtotime($dateTo)) {
    $errors[] = "Start date cannot be after end date.";
}

if ($errors) {
    $_SESSION['error_message'] = implode(" ", $errors);
    header("Location: manage_users.php");
    exit;
}

// Build query with prepared statement
$query = "SELECT user_id, full_name, email, phone, role, status, created_at, updated_at FROM users WHERE 1=1";
$params = [];
$types = "";

if ($role) {
    $query .= " AND role = ?";
    $params[] = $role;
    $types .= "s";
}
if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($dateFrom) {
    $query .= " AND created_at >= ?";
    $params[] = $dateFrom . " 00:00:00";
    $types .= "s";
}
if ($dateTo) {
    $query .= " AND created_at <= ?";
    $params[] = $dateTo . " 23:59:59";
    $types .= "s";
}
$query .= " ORDER BY $sortBy $sortOrder";

try {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching users: " . $e->getMessage();
    header("Location: manage_users.php");
    exit;
}

if (empty($users)) {
    $_SESSION['error_message'] = "No users found for the selected filters.";
    header("Location: manage_users.php");
    exit;
}

if ($format === 'csv') {
    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_report_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Phone', 'Role', 'Status', 'Joined', 'Last Updated']);

    foreach ($users as $user) {
        fputcsv($output, [
            substr($user['user_id'], 0, 8),
            $user['full_name'],
            $user['email'],
            $user['phone'] ?? 'N/A',
            ucfirst($user['role']),
            ucfirst($user['status']),
            date('M j, Y', strtotime($user['created_at'])),
            $user['updated_at'] ? date('M j, Y', strtotime($user['updated_at'])) : 'N/A'
        ]);
    }

    fclose($output);
    exit;
} else {
    // Generate PDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // For external images, if needed
    $dompdf = new Dompdf($options);

    $html = '
    <style>
        body { 
            font-family: "Helvetica", sans-serif; 
            font-size: 12px; 
            color: #263238; 
        }
        h1 { 
            color: #1e88e5; 
            font-size: 24px; 
            margin-bottom: 10px; 
        }
        p { 
            margin: 5px 0; 
            color: #455a64; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #b0bec5; 
            padding: 10px; 
            text-align: left; 
        }
        th { 
            background-color: #1e88e5; 
            color: white; 
            font-weight: bold; 
        }
        tr:nth-child(even) { 
            background-color: #eceff1; 
        }
        .logo { 
            position: absolute; 
            top: 10px; 
            left: 10px; 
            width: 100px; 
        }
        .footer { 
            text-align: center; 
            font-size: 10px; 
            color: #78909c; 
            margin-top: 20px; 
        }
    </style>
    <img src="https://via.placeholder.com/100x40?text=Logo" class="logo" alt="JIGJIGAHOMES Logo">
    <h1>Users Report</h1>
    <p>Generated on: ' . date('M j, Y H:i') . '</p>
    <p>Filters: Role=' . ($role ?: 'All') . ', Status=' . ($status ?: 'All') . 
    ', From=' . ($dateFrom ?: 'All') . ', To=' . ($dateTo ?: 'All') . '</p>
    <table>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Last Updated</th>
        </tr>';

    foreach ($users as $user) {
        $html .= '<tr>
            <td>' . substr($user['user_id'], 0, 8) . '</td>
            <td>' . htmlspecialchars($user['full_name']) . '</td>
            <td>' . htmlspecialchars($user['email']) . '</td>
            <td>' . htmlspecialchars($user['phone'] ?? 'N/A') . '</td>
            <td>' . ucfirst($user['role']) . '</td>
            <td>' . ucfirst($user['status']) . '</td>
            <td>' . date('M j, Y', strtotime($user['created_at'])) . '</td>
            <td>' . ($user['updated_at'] ? date('M j, Y', strtotime($user['updated_at'])) : 'N/A') . '</td>
        </tr>';
    }

    $html .= '</table>
    <div class="footer">
        JIGJIGAHOMES - House Rental System &copy; ' . date('Y') . '
    </div>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("users_report_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
    exit;
}

$conn->close();
?>