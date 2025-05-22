<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
        die("Unauthorized action.");
    }

    $tenant_id = $_SESSION['user_id'];

    // Validate UUID format
    $property_id = $_POST["property_id"];
    if (!preg_match('/^[0-9a-fA-F-]{36}$/', $property_id)) {
        die("❌ Invalid property ID format.");
    }

    if (!preg_match('/^[0-9a-fA-F-]{36}$/', $tenant_id)) {
        die("❌ Invalid tenant ID format.");
    }

    $start_date = $_POST["start_date"];
    $lease_months = intval($_POST["lease_months"]);
    $monthly_rent = floatval($_POST["monthly_rent"]);

    if (!$start_date || $lease_months < 1 || $lease_months > 36) {
        die("❌ Invalid start date or lease duration.");
    }

    // Calculate end_date from start_date + lease_months
    $start = new DateTime($start_date);
    $end = clone $start;
    $end->modify("+$lease_months months");

    // Adjust for month overflow (e.g., Jan 31 + 1 month = Feb 28)
    if ($start->format('d') !== $end->format('d')) {
        $end->modify('last day of previous month');
    }

    $end_date = $end->format('Y-m-d');

    if (strtotime($start_date) > strtotime($end_date)) {
        die("❌ Start date must be before end date.");
    }

    // Insert lease using UUIDs (tenant_id and property_id are UUIDs)
    $stmt = $conn->prepare("
        INSERT INTO leases (
            tenant_id, property_id, start_date, end_date, monthly_rent
        ) VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssd", $tenant_id, $property_id, $start_date, $end_date, $monthly_rent);

    if ($stmt->execute()) {
        // Redirect to generate invoice (pass tenant_id/property_id if needed)
        header("Location: generate_invoice.php?tenant_id=" . urlencode($tenant_id) . "&property_id=" . urlencode($property_id));
        exit();
    } else {
        echo "❌ Error inserting lease: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
