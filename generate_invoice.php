<?php
session_start();


include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    die("Access denied.");
}

if (!isset($_GET['property_id'])) {
    die("Property ID is required.");
}

// Relaxed UUID format validation for property_id
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $_GET['property_id'])) {
    die("Invalid property ID format.");
}

$property_id = $_GET['property_id'];
$tenant_id = $_SESSION['user_id'];

// Fetch the lease that belongs to this tenant for the given property
$stmt = $conn->prepare("
    SELECT lease_id, monthly_rent 
    FROM leases 
    WHERE property_id = ? AND tenant_id = ?
    LIMIT 1
");
$stmt->bind_param("ss", $property_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$lease = $result->fetch_assoc();

if (!$lease) {
    die("Lease not found for this property or you do not have access.");
}

$lease_id = $lease['lease_id'];
$amount_due = $lease['monthly_rent'];

// Insert invoice
function generate_uuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$invoice_id = generate_uuid();


$insert = $conn->prepare("INSERT INTO invoices (invoice_id, lease_id, amount_due) VALUES (?, ?, ?)");
$insert->bind_param("ssd", $invoice_id, $lease_id, $amount_due);

if ($insert->execute()) {
    header("Location: pay_invoice.php?invoice_id=" . urlencode($invoice_id));
    exit();
} else {
    echo "Failed to create invoice: " . $insert->error;
}

?>
