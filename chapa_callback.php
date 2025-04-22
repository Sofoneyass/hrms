<?php
session_start();
include 'db_connection.php';

$tx_ref = $_GET['tx_ref'] ?? '';
$invoice_id = intval($_GET['invoice_id']);

if (!$tx_ref) {
    die("Transaction reference is required.");
}

// Verify the payment with Chapa
$ch = curl_init("https://api.chapa.co/v1/transaction/verify/" . $tx_ref);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer CHASECK_TEST-SXgyDdIdRckExBl8N5gkbBmtLZH4An6B',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (
    isset($result['status']) && $result['status'] === 'success' &&
    isset($result['data']['status']) && $result['data']['status'] === 'success'
) {
    // Fetch user_id via invoice and lease relationship
    $stmt = $conn->prepare("SELECT l.tenant_id, i.amount_due 
                            FROM invoices i 
                            JOIN leases l ON i.lease_id = l.lease_id 
                            WHERE i.invoice_id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $invoiceData = $stmt->get_result()->fetch_assoc();

    if (!$invoiceData) {
        die("Invoice not linked to a lease/tenant.");
    }

    $user_id = $invoiceData['tenant_id'];
    $amount = $invoiceData['amount_due'];
    $payment_status = 'completed';
    $transaction_reference = $result['data']['tx_ref'];
    $chapa_payment_id = $result['data']['payment_id'];

    // Insert the payment into the payments table
    $insertStmt = $conn->prepare("INSERT INTO payments (
        invoice_id, user_id, amount, transaction_reference, payment_status, chapa_payment_id
    ) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("iidsss", $invoice_id, $user_id, $amount, $transaction_reference, $payment_status, $chapa_payment_id);

    if ($insertStmt->execute()) {
        echo "✅ Payment recorded and verified successfully for Invoice #" . $invoice_id;
    } else {
        echo "❌ Payment verified but failed to record in the database.";
    }

} else {
    echo "❌ Payment verification failed for Invoice #" . $invoice_id;
}
?>
