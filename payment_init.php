<?php
session_start();
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$invoice_id = intval($_POST['invoice_id']);
$amount = floatval($_POST['amount']);

// Fetch tenant + invoice info
$stmt = $conn->prepare("SELECT i.*, u.full_name, u.email, u.phone 
                        FROM invoices i
                        JOIN leases l ON i.lease_id = l.lease_id
                        JOIN users u ON l.tenant_id = u.user_id
                        WHERE i.invoice_id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();

if (!$invoice) {
    die("Invoice not found.");
}

$tx_ref = 'INV-' . $invoice_id . '-' . time();

$data = [
    'public_key' => 'CHAPUBK_TEST-2DENmejpcbzPRW9XtZjMKokUfmeEfrJM',
    'amount' => $amount,
    'currency' => 'ETB',
    'email' => $invoice['email'],
    'first_name' => explode(" ", $invoice['full_name'])[0],
    'last_name' => explode(" ", $invoice['full_name'])[1] ?? '',
    'phone_number' => $invoice['phone'],
    'tx_ref' => $tx_ref,
    'callback_url' => "http://localhost/h/chapa_callback.php?tx_ref=$tx_ref&invoice_id=$invoice_id",
    'return_url' => "http://localhost/h/payment_success.php?invoice_id=$invoice_id",
    'customization' => [
        'title' => 'House Rental Pay',
        'description' => 'Invoice Payment for Lease'
    ]
];

$ch = curl_init("https://api.chapa.co/v1/transaction/initialize");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer CHASECK_TEST-SXgyDdIdRckExBl8N5gkbBmtLZH4An6B',  // Replace with your actual secret key
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);

if (isset($responseData['status']) && $responseData['status'] == 'success') {
    // Get payment details
    $transaction_reference = $responseData['data']['transaction_reference'];
    $checkout_url = $responseData['data']['checkout_url'];
    
    // Store payment info into the database
    $payment_status = 'pending'; // Set initial status as pending
    $user_id = $invoice['tenant_id']; // Assuming tenant_id is available in the invoice query

    // Insert payment details into the payments table
    $insertStmt = $conn->prepare("INSERT INTO payments (invoice_id, user_id, amount, transaction_reference, payment_status) 
                                 VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("iiiss", $invoice_id, $user_id, $amount, $transaction_reference, $payment_status);
    $insertStmt->execute();

    // Redirect user to the payment checkout page
    header("Location: " . $checkout_url);
    exit();
} else {
    echo "Payment initialization failed.";
    echo "<pre>";
    print_r($responseData);
    echo "</pre>";
}
?>
