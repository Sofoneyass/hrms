<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    die("Access denied.");
}

if (!isset($_GET['lease_id'])) {
    die("Lease ID required.");
}

$lease_id = intval($_GET['lease_id']);

// Fetch lease details
$query = $conn->prepare("SELECT * FROM leases WHERE lease_id = ?");
$query->bind_param("i", $lease_id);
$query->execute();
$result = $query->get_result();
$lease = $result->fetch_assoc();

if (!$lease) {
    die("Lease not found.");
}

$amount_due = $lease['monthly_rent'];
$tenant_id = $lease['tenant_id'];

// Create invoice
$insert = $conn->prepare("INSERT INTO invoices (lease_id, amount_due) VALUES (?, ?)");
$insert->bind_param("id", $lease_id, $amount_due);

if ($insert->execute()) {
    $invoice_id = $insert->insert_id;

    // Redirect to payment page with invoice_id
    header("Location: pay_invoice.php?invoice_id=" . $invoice_id);
    exit();
} else {
    echo "Failed to create invoice: " . $insert->error;
}
