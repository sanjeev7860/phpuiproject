<?php
require 'config.php';

if (!$_SESSION['cart']) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (
        $name === '' ||
        !preg_match('/^[0-9]{10}$/', $phone) ||
        $address === ''
    ) {
        $error = "Please enter valid customer details.";
    } else {
        try {
            $pdo->beginTransaction();

            $total = 0;
            $orderItems = [];

            foreach ($_SESSION['cart'] as $id => $qty) {
                $stmt = $pdo->prepare(
                    "SELECT * FROM products
                     WHERE id=? AND status=1 FOR UPDATE"
                );
                $stmt->execute([$id]);
                $p = $stmt->fetch();

                if (!$p || $qty > $p['stock'] || $qty < 1) {
                    throw new Exception(
                        "Some products are unavailable. Please update cart."
                    );
                }

                $total += $p['price'] * $qty;
                $orderItems[] = [
                    'product' => $p,
                    'qty' => $qty
                ];
            }

            $stmt = $pdo->prepare(
                "INSERT INTO orders
                (customer_name, phone, address, total,
                 payment_method, payment_status, order_status)
                VALUES (?, ?, ?, ?, 'UPI', 'Pending', 'Pending')"
            );

            $stmt->execute([$name, $phone, $address, $total]);

            $orderId = $pdo->lastInsertId();

            foreach ($orderItems as $item) {
                $p = $item['product'];
                $qty = $item['qty'];

                $stmt = $pdo->prepare(
                    "INSERT INTO order_items
                    (order_id, product_id, product_name, price, quantity)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $orderId,
                    $p['id'],
                    $p['name'],
                    $p['price'],
                    $qty
                ]);

                $stmt = $pdo->prepare(
                    "UPDATE products SET stock=stock-? WHERE id=?"
                );
                $stmt->execute([$qty, $p['id']]);
            }

            $pdo->commit();

            $_SESSION['cart'] = [];

            $_SESSION['last_order'] = $orderId;

            header("Location: payment.php?order=".$orderId);
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e instanceof PDOException
                ? "Unable to place order. Please try again."
                : $e->getMessage();
        }
    }
}

$total = getCartTotal($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout - Messo</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <a href="index.php" class="logo">messo<span>.</span></a>
    <a href="cart.php" class="cart-btn">← Cart</a>
</header>

<main class="container checkout-container">

    <h2>Checkout</h2>

    <?php if ($error): ?>
        <div class="error"><?=e($error)?></div>
    <?php endif; ?>

    <form method="POST" class="checkout-form">

        <input type="hidden" name="csrf"
               value="<?=e($_SESSION['csrf'])?>">

        <h3>Delivery Details</h3>

        <label>Full Name</label>
        <input type="text" name="name" maxlength="150"
               required placeholder="Enter your name">

        <label>Mobile Number</label>
        <input type="tel" name="phone" pattern="[0-9]{10}"
               maxlength="10" required placeholder="10 digit mobile number">

        <label>Full Delivery Address</label>
        <textarea name="address" required maxlength="1000"
                  placeholder="House no, street, city, pincode"></textarea>

        <h3>Payment Method</h3>

        <div class="upi-option">
            <input type="radio" checked disabled>
            <div>
                <strong>UPI Payment Only</strong>
                <p>Google Pay / PhonePe / BHIM / Other UPI Apps</p>
            </div>
        </div>

        <div class="total-box">
            Total Amount:
            <strong>₹<?=number_format($total, 2)?></strong>
        </div>

        <button class="checkout-btn" type="submit">
            Place Order & Pay via UPI
        </button>

    </form>

</main>

</body>
</html>