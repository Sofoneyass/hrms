<?php
session_start();
require_once 'db_connection.php';

// Initialize filter parameters
$filters = [
    'title' => $_GET['title'] ?? '',
    'status' => $_GET['status'] ?? '',
    'owner_id' => $_GET['owner_id'] ?? '',
];

// Build query
$whereClauses = [];
$params = [];
$paramTypes = '';

if ($filters['title']) {
    $whereClauses[] = "p.title LIKE ?";
    $params[] = '%' . $filters['title'] . '%';
    $paramTypes .= 's';
}
if ($filters['status']) {
    $whereClauses[] = "p.status = ?";
    $params[] = $filters['status'];
    $paramTypes .= 's';
}
if ($filters['owner_id']) {
    $whereClauses[] = "p.owner_id = ?";
    $params[] = $filters['owner_id'];
    $paramTypes .= 's';
}

$whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    // Get properties
    $propertyQuery = "
        SELECT p.*, u.full_name as owner_name 
        FROM properties p
        LEFT JOIN users u ON p.owner_id = u.user_id
        $whereSql
        ORDER BY p.created_at DESC
    ";
    $stmt = $conn->prepare($propertyQuery);
    if ($paramTypes) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get bookings for all properties
    $bookingQuery = "
        SELECT b.*, u.full_name as tenant_name, b.property_id
        FROM bookings b
        LEFT JOIN users u ON b.tenant_id = u.user_id
        WHERE b.property_id IN (SELECT property_id FROM properties p $whereSql)
    ";
    $stmt = $conn->prepare($bookingQuery);
    if ($paramTypes) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get maintenance requests for all properties
    $maintenanceQuery = "
        SELECT mr.*, u.full_name as tenant_name, mr.property_id
        FROM maintenance_requests mr
        LEFT JOIN users u ON mr.lease_id IN (SELECT lease_id FROM leases WHERE tenant_id = u.user_id)
        WHERE mr.property_id IN (SELECT property_id FROM properties p $whereSql)
    ";
    $stmt = $conn->prepare($maintenanceQuery);
    if ($paramTypes) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $maintenance_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Organize bookings and maintenance by property_id
    $bookings_by_property = [];
    foreach ($bookings as $booking) {
        $bookings_by_property[$booking['property_id']][] = $booking;
    }
    $maintenance_by_property = [];
    foreach ($maintenance_requests as $request) {
        $maintenance_by_property[$request['property_id']][] = $request;
    }

    // Build table rows
    $table_rows = [];
    foreach ($properties as $property) {
        $property_bookings = $bookings_by_property[$property['property_id']] ?? [];
        $property_maintenance = $maintenance_by_property[$property['property_id']] ?? [];

        // If no bookings or maintenance, add one row with property details
        if (empty($property_bookings) && empty($property_maintenance)) {
            $table_rows[] = [
                'property_id' => substr($property['property_id'], 0, 8),
                'title' => htmlspecialchars($property['title']),
                'location' => htmlspecialchars($property['location']),
                'owner' => htmlspecialchars($property['owner_name'] ?? 'N/A'),
                'bedrooms' => $property['bedrooms'],
                'bathrooms' => $property['bathrooms'],
                'price' => '$' . number_format($property['price_per_month'], 2),
                'status' => ucfirst($property['status']),
                'created_at' => date('Y-m-d H:i:s', strtotime($property['created_at'])),
                'booking_tenant' => 'N/A',
                'booking_start' => 'N/A',
                'booking_end' => 'N/A',
                'booking_status' => 'N/A',
                'maintenance_tenant' => 'N/A',
                'maintenance_date' => 'N/A',
                'maintenance_status' => 'N/A',
                'maintenance_description' => 'N/A',
            ];
        } else {
            // Add rows for each booking
            foreach ($property_bookings as $booking) {
                $row = [
                    'property_id' => substr($property['property_id'], 0, 8),
                    'title' => htmlspecialchars($property['title']),
                    'location' => htmlspecialchars($property['location']),
                    'owner' => htmlspecialchars($property['owner_name'] ?? 'N/A'),
                    'bedrooms' => $property['bedrooms'],
                    'bathrooms' => $property['bathrooms'],
                    'price' => '$' . number_format($property['price_per_month'], 2),
                    'status' => ucfirst($property['status']),
                    'created_at' => date('Y-m-d H:i:s', strtotime($property['created_at'])),
                    'booking_tenant' => htmlspecialchars($booking['tenant_name'] ?? 'N/A'),
                    'booking_start' => $booking['start_date'],
                    'booking_end' => $booking['end_date'],
                    'booking_status' => ucfirst($booking['status']),
                    'maintenance_tenant' => 'N/A',
                    'maintenance_date' => 'N/A',
                    'maintenance_status' => 'N/A',
                    'maintenance_description' => 'N/A',
                ];
                $table_rows[] = $row;
            }

            // Add rows for each maintenance request
            foreach ($property_maintenance as $request) {
                $row = [
                    'property_id' => substr($property['property_id'], 0, 8),
                    'title' => htmlspecialchars($property['title']),
                    'location' => htmlspecialchars($property['location']),
                    'owner' => htmlspecialchars($property['owner_name'] ?? 'N/A'),
                    'bedrooms' => $property['bedrooms'],
                    'bathrooms' => $property['bathrooms'],
                    'price' => '$' . number_format($property['price_per_month'], 2),
                    'status' => ucfirst($property['status']),
                    'created_at' => date('Y-m-d H:i:s', strtotime($property['created_at'])),
                    'booking_tenant' => 'N/A',
                    'booking_start' => 'N/A',
                    'booking_end' => 'N/A',
                    'booking_status' => 'N/A',
                    'maintenance_tenant' => htmlspecialchars($request['tenant_name'] ?? 'N/A'),
                    'maintenance_date' => date('Y-m-d H:i:s', strtotime($request['request_date'])),
                    'maintenance_status' => ucfirst($request['status']),
                    'maintenance_description' => htmlspecialchars($request['description'] ?? 'N/A'),
                ];
                $table_rows[] = $row;
            }

            // If no bookings, add a row for maintenance or property alone
            if (empty($property_bookings) && !empty($property_maintenance)) {
                $row = [
                    'property_id' => substr($property['property_id'], 0, 8),
                    'title' => htmlspecialchars($property['title']),
                    'location' => htmlspecialchars($property['location']),
                    'owner' => htmlspecialchars($property['owner_name'] ?? 'N/A'),
                    'bedrooms' => $property['bedrooms'],
                    'bathrooms' => $property['bathrooms'],
                    'price' => '$' . number_format($property['price_per_month'], 2),
                    'status' => ucfirst($property['status']),
                    'created_at' => date('Y-m-d H:i:s', strtotime($property['created_at'])),
                    'booking_tenant' => 'N/A',
                    'booking_start' => 'N/A',
                    'booking_end' => 'N/A',
                    'booking_status' => 'N/A',
                    'maintenance_tenant' => 'N/A',
                    'maintenance_date' => 'N/A',
                    'maintenance_status' => 'N/A',
                    'maintenance_description' => 'N/A',
                ];
                $table_rows[] = $row;
            }
        }
    }

    // Display HTML report
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Overall Properties Report</title>
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
                <h1 class="font-bold text-center">Overall Properties Report</h1>
                <p class="text-center">Generated on <?php echo date('Y-m-d'); ?></p>

                <?php if (empty($table_rows)): ?>
                    <p class="text-center">No data found for the selected filters.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Property ID</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Owner</th>
                                <th>Bedrooms</th>
                                <th>Bathrooms</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Booking Tenant</th>
                                <th>Booking Start</th>
                                <th>Booking End</th>
                                <th>Booking Status</th>
                                <th>Maintenance Tenant</th>
                                <th>Maintenance Date</th>
                                <th>Maintenance Status</th>
                                <th>Maintenance Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_rows as $row): ?>
                                <tr>
                                    <td><?php echo $row['property_id']; ?></td>
                                    <td><?php echo $row['title']; ?></td>
                                    <td><?php echo $row['location']; ?></td>
                                    <td><?php echo $row['owner']; ?></td>
                                    <td><?php echo $row['bedrooms']; ?></td>
                                    <td><?php echo $row['bathrooms']; ?></td>
                                    <td><?php echo $row['price']; ?></td>
                                    <td><?php echo $row['status']; ?></td>
                                    <td><?php echo $row['created_at']; ?></td>
                                    <td><?php echo $row['booking_tenant']; ?></td>
                                    <td><?php echo $row['booking_start']; ?></td>
                                    <td><?php echo $row['booking_end']; ?></td>
                                    <td><?php echo $row['booking_status']; ?></td>
                                    <td><?php echo $row['maintenance_tenant']; ?></td>
                                    <td><?php echo $row['maintenance_date']; ?></td>
                                    <td><?php echo $row['maintenance_status']; ?></td>
                                    <td><?php echo $row['maintenance_description']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Print/Save Button and Spinner -->
                <div class="text-center no-print">
                    <button id="printButton" onclick="printReport()" class="btn btn-success px-3 py-1 text-sm bg-[#2a7f62] hover:bg-[#226b4f] rounded">
                        <i class="fas fa-file-pdf"></i> Print/Save as PDF
                    </button>
                    <div id="spinner" class="spinner"></div>
                    <p id="redirectMessage" class="text-sm mt-2 hidden">Preparing PDF, redirecting to Manage Properties...</p>
                </div>
            </div>
        </div>
        <script>
            function printReport() {
                // Hide button, show spinner and message
                document.getElementById('printButton').classList.add('hidden');
                document.getElementById('spinner').style.display = 'block';
                document.getElementById('redirectMessage').classList.remove('hidden');

                // Trigger print dialog
                window.print();

                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = 'manage_properties.php';
                }, 2000);
            }
        </script>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error generating report: " . $e->getMessage();
    header("Location: manage_properties.php");
    exit;
}

$conn->close();
?>