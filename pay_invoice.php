<?php
include 'db_connection.php';

if (!isset($_GET['invoice_id'])) {
    die("Invoice ID is required.");
}

$invoice_id = $_GET['invoice_id'];

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $invoice_id)) {
    die("Invalid invoice ID format.");
}

$query = $conn->prepare("
    SELECT i.*, l.property_id, l.tenant_id, l.monthly_rent 
    FROM invoices i 
    JOIN leases l ON i.lease_id = l.lease_id 
    WHERE i.invoice_id = ?
");
$query->bind_param("s", $invoice_id);
$query->execute();
$result = $query->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    die("Invoice not found.");
}

// Use monthly_rent from lease as amount_due
$amount_due = $invoice['monthly_rent'];

?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice | JIGJIGAHOMES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #4A6FA5; /* Ethiopian blue */
            --secondary: #6dd5fa;
            --accent: #2980b9;
            --text: #333;
            --light: #f8f9fa;
            --dark-bg: #121212;
            --dark-text: #e2e8f0;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-dark-bg: rgba(30, 41, 59, 0.3);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        body[data-theme="dark"] {
            background: linear-gradient(135deg, #121212 0%, #1e1e1e 100%);
            color: var(--dark-text);
        }

        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 20px;
            flex: 1;
        }

        .payment-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 0.5s ease;
        }

        body[data-theme="dark"] .payment-card {
            background: var(--glass-dark-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .payment-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .payment-header h1 {
            font-size: 1.8rem;
            color: var(--primary);
            margin: 0;
        }

        body[data-theme="dark"] .payment-header h1 {
            color: #8ab4f8;
        }

        .payment-details {
            margin-bottom: 2rem;
        }

        .payment-details p {
            font-size: 1.1rem;
            margin: 0.5rem 0;
        }

        .payment-details .amount-due {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--accent);
        }

        body[data-theme="dark"] .payment-details .amount-due {
            color: var(--secondary);
        }

        .payment-form {
            display: flex;
            justify-content: center;
        }

        .btn-pay {
            background-color: var(--primary);
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-pay:hover {
            background-color: #3a5a8c;
            transform: translateY(-2px);
        }

        .btn-pay:focus {
            outline: 3px solid var(--secondary);
            outline-offset: 2px;
        }

        footer {
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: auto;
        }

        body[data-theme="dark"] footer {
            color: #9ca3af;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 600px) {
            .payment-container {
                margin: 1rem auto;
                padding: 0 15px;
            }

            .payment-card {
                padding: 1.5rem;
            }

            .payment-header h1 {
                font-size: 1.5rem;
            }

            .payment-details .amount-due {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <?php require_once 'header.php'; ?>
    </header>

    <main class="payment-container">
        <section class="payment-card" aria-labelledby="payment-heading">
            <div class="payment-header">
                <h1 id="payment-heading">Pay Invoice #<?= htmlspecialchars($invoice_id) ?></h1>
            </div>
            <div class="payment-details">
                <p>Amount Due: <span class="amount-due">ETB <?= htmlspecialchars(number_format($amount_due, 2)) ?></span></p>
            </div>
            <form action="payment_init.php" method="POST" class="payment-form">
                <input type="hidden" name="invoice_id" value="<?= htmlspecialchars($invoice_id) ?>">
                <input type="hidden" name="amount" value="<?= htmlspecialchars($amount_due) ?>">
                <button type="submit" class="btn-pay" aria-label="Pay invoice with Chapa">
                    <i class="fas fa-credit-card"></i> Pay with Chapa
                </button>
            </form>
        </section>
    </main>

    <footer>
         <?php require_once 'footer.php'; ?>
    </footer>
</body>
</html>
