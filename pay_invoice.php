<?php
session_start();
include 'db_connection.php';

if (!isset($_GET['invoice_id'])) {
    die("Invoice ID is required.");
}

$invoice_id = intval($_GET['invoice_id']);

$query = $conn->prepare("SELECT i.*, l.property_id, l.tenant_id 
                         FROM invoices i 
                         JOIN leases l ON i.lease_id = l.lease_id 
                         WHERE i.invoice_id = ?");
$query->bind_param("i", $invoice_id);
$query->execute();
$result = $query->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    die("Invoice not found.");
}

$amount_due = $invoice['amount_due'];
?>

<h2>Pay Invoice #<?= $invoice_id ?></h2>
<p>Amount Due: <strong>ETB <?= number_format($amount_due, 2) ?></strong></p>

<form action="payment_init.php" method="POST">
  <input type="hidden" name="invoice_id" value="<?= $invoice_id ?>">
  <input type="hidden" name="amount" value="<?= $amount_due ?>">
  <button type="submit">Pay with Chapa</button>
</form>
