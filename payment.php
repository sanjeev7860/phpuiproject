<?php
require 'config.php';

$orderId = filter_input(INPUT_GET, 'order', FILTER_VALIDATE_INT);

if (!$orderId || !isset($_SESSION['last_order']) ||
    (int)$_SESSION['last_order'] !== (int)$orderId) {
    http_response_code(403);
    exit('Invalid order access.');
}

$stmt = $pdo->prepare(
    "SELECT * FROM orders WHERE id=?"
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    exit('Order not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $utr = trim($_POST['utr'] ?? '');

    if (!preg_match('/^[A-Za-z0-9]{8,30}$/', $utr)) {
        $error = "Please enter a valid UTR / transaction reference.";
    } elseif ($order['payment_status'] !== 'Pending') {
        $error = "Payment details have already been submitted.";
    } else {
        $stmt = $pdo->prepare(
            "UPDATE orders
             SET utr_number=?, payment_status='Verification Pending'
             WHERE id=? AND payment_status='Pending'"
        );

        $stmt->execute([$utr, $orderId]);

        header("Location: payment.php?order=".$orderId);
        exit;
    }
}

$upiLink = "upi://pay?" . http_build_query([
    'pa' => UPI_ID,
    'pn' => UPI_NAME,
    'am' => number_format($order['total'], 2, '.', ''),
    'cu' => 'INR',
    'tn' => 'Messo Order '.$orderId
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UPI Payment - Messo</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <a href="index.php" class="logo">messo<span>.</span></a>
</header>

<main class="payment-container">

    <h2>Complete Your UPI Payment</h2>

    <div class="payment-card">

        <p>Order Number: #<?=e($order['id'])?></p>

        <h1>₹<?=number_format($order['total'], 2)?></h1>

        <div class="upi-logo">UPI</div>

        <p>Pay using any UPI application.</p>

        <p><strong>UPI ID:</strong> <?=e(UPI_ID)?></p>

        <a href="<?=e($upiLink)?>" class="checkout-btn">
            Open UPI App & Pay
        </a>

        <p class="payment-note">
            After completing payment, enter your transaction reference
            number below. Your payment will be manually verified.
        </p>

        <?php if ($error): ?>
            <div class="error"><?=e($error)?></div>
        <?php endif; ?>

        <?php if ($order['payment_status'] === 'Pending'): ?>

        <form method="POST" class="utr-form">

            <input type="hidden" name="csrf"
                   value="<?=e($_SESSION['csrf'])?>">

            <label>UPI UTR / Transaction Reference Number</label>

            <input type="text" name="utr" required
                   minlength="8" maxlength="30"
                   placeholder="Enter transaction reference">

            <button class="checkout-btn" type="submit">
                Submit Payment Details
            </button>

        </form>

        <?php else: ?>

            <div class="success">
                <h3>Payment Submitted!</h3>
                <p>Status: <?=e($order['payment_status'])?></p>
                <p>Order Status: <?=e($order['order_status'])?></p>
                <p>Our team will verify your payment.</p>
            </div>

        <?php endif; ?>

    </div>

</main>

</body>
</html>