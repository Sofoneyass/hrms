<?php
session_start();
require_once 'db_connection.php';

if (!isset($_POST['property_id'])) {
    $_SESSION['error_message'] = "Property ID not provided.";
    header("Location: manage_properties.php");
    exit;
}

$propertyId = $_POST['property_id'];

try {
    // Fetch property details
    $stmt = $conn->prepare("
        SELECT p.*, u.full_name as owner_name 
        FROM properties p
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE p.property_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$property) {
        throw new Exception("Property not found.");
    }

    // Fetch bookings
    $stmt = $conn->prepare("
        SELECT b.*, u.full_name as tenant_name
        FROM bookings b
        LEFT JOIN users u ON b.tenant_id = u.user_id
        WHERE b.property_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch maintenance requests
    $stmt = $conn->prepare("
        SELECT mr.*, u.full_name as tenant_name
        FROM maintenance_requests mr
        LEFT JOIN users u ON mr.lease_id IN (SELECT lease_id FROM leases WHERE tenant_id = u.user_id)
        WHERE mr.property_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $maintenance_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Display HTML report
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Property Report: <?php echo htmlspecialchars($property['title']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            body {
                background: #1f2937;
                color: #ffffff;
                font-family: Arial, sans-serif;
            }
            .glass {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 8px;
                padding: 12px;
            }
            .table {
                width: 100%;
                max-width: 800px;
                border-collapse: collapse;
                margin-bottom: 12px;
                font-size: 12px;
            }
            .table th, .table td {
                border: 1px solid #e5e7eb;
                padding: 4px;
                text-align: left;
            }
            .table th {
                background-color: #1e40af;
                color: #ffffff;
            }
            h1 {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }
            h2 {
                font-size: 1.125rem;
                margin-bottom: 8px;
                margin-top: 12px;
            }
            p {
                font-size: 0.875rem;
                margin-bottom: 8px;
            }
            .container {
                max-width: 900px;
                padding: 16px;
            }
            .spinner {
                display: none;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #16a34a;
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
                h2 {
                    font-size: 12pt;
                    margin-bottom: 5mm;
                    margin-top: 10mm;
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
                <h1 class="font-bold text-center">Property Report: <?php echo htmlspecialchars($property['title']); ?></h1>
                <p class="text-center">Generated on <?php echo date('Y-m-d'); ?></p>

                <!-- Property Details -->
                <h2 class="font-semibold">Property Details</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ID</td>
                            <td><?php echo substr($property['property_id'], 0, 8); ?></td>
                        </tr>
                        <tr>
                            <td>Title</td>
                            <td><?php echo htmlspecialchars($property['title']); ?></td>
                        </tr>
                        <tr>
                            <td>Location</td>
                            <td><?php echo htmlspecialchars($property['location']); ?></td>
                        </tr>
                        <tr>
                            <td>Owner</td>
                            <td><?php echo htmlspecialchars($property['owner_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td>Bedrooms</td>
                            <td><?php echo $property['bedrooms']; ?></td>
                        </tr>
                        <tr>
                            <td>Bathrooms</td>
                            <td><?php echo $property['bathrooms']; ?></td>
                        </tr>
                        <tr>
                            <td>Price per Month</td>
                            <td>$<?php echo number_format($property['price_per_month'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td><?php echo ucfirst($property['status']); ?></td>
                        </tr>
                        <tr>
                            <td>Created At</td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($property['created_at'])); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Bookings -->
                <h2 class="font-semibold">Bookings</h2>
                <?php if (empty($bookings)): ?>
                    <p>No bookings found for this property.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['tenant_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $booking['start_date']; ?></td>
                                    <td><?php echo $booking['end_date']; ?></td>
                                    <td><?php echo ucfirst($booking['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Maintenance Requests -->
                <h2 class="font-semibold">Maintenance Requests</h2>
                <?php if (empty($maintenance_requests)): ?>
                    <p>No maintenance requests found for this property.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maintenance_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['tenant_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($request['request_date'])); ?></td>
                                    <td><?php echo ucfirst($request['status']); ?></td>
                                    <td><?php echo htmlspecialchars($request['description'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Print/Save Button and Spinner -->
                <div class="text-center no-print">
                    <button id="printButton" onclick="printReport()" class="btn btn-success px-3 py-1 text-sm bg-green-600 hover:bg-green-700 rounded">
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