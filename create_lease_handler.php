<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
        die("Unauthorized action.");
    }

    $tenant_id = $_SESSION['user_id'];
    $property_id = intval($_POST["property_id"]);
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $monthly_rent = floatval($_POST["monthly_rent"]);

    if (strtotime($start_date) > strtotime($end_date)) {
        die("❌ Start date must be before end date.");
    }

    // Insert lease
    $stmt = $conn->prepare("INSERT INTO leases (tenant_id, property_id, start_date, end_date, monthly_rent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissd", $tenant_id, $property_id, $start_date, $end_date, $monthly_rent);

    if ($stmt->execute()) {
        $lease_id = $stmt->insert_id;

        // Optionally, create an invoice here immediately (optional auto-billing step)
        // You can also redirect to a payment page where invoice will be created
        header("Location: generate_invoice.php?lease_id=" . $lease_id);
        exit();
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
