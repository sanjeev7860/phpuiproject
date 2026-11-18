<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($id && isset($_SESSION['cart'][$id])) {
        if ($action === 'remove') {
            unset($_SESSION['cart'][$id]);
        } elseif ($action === 'update') {
            $qty = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

            if ($qty === false || $qty === null || $qty < 1) {
                unset($_SESSION['cart'][$id]);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT stock FROM products WHERE id=? AND status=1"
                );
                $stmt->execute([$id]);
                $product = $stmt->fetch();

                if ($product) {
                    $_SESSION['cart'][$id] = min(99, $product['stock'], $qty);
                } else {
                    unset($_SESSION['cart'][$id]);
                }
            }
        }
    }

    header('Location: cart.php');
    exit;
}

$items = [];

foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE id=? AND status=1"
    );
    $stmt->execute([$id]);
    $p = $stmt->fetch();

    if ($p && $p['stock'] > 0) {
        $qty = min($qty, $p['stock']);
        $_SESSION['cart'][$id] = $qty;
        $items[] = ['product' => $p, 'qty' => $qty];
    } else {
        unset($_SESSION['cart'][$id]);
    }
}

$total = getCartTotal($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shopping Cart - Messo</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <a href="index.php" class="logo">messo<span>.</span></a>
    <a href="index.php" class="cart-btn">← Continue Shopping</a>
</header>

<main class="container">
    <h2>My Shopping Cart</h2>

    <?php if (!$items): ?>

        <div class="empty">
            <h2>Your cart is empty 🛒</h2>
            <a href="index.php" class="hero-btn">Shop Now</a>
        </div>

    <?php else: ?>

        <div class="cart-layout">

            <div class="cart-items">

                <?php foreach ($items as $item):
                    $p = $item['product'];
                    $qty = $item['qty'];
                ?>

                <div class="cart-item">

                    <img src="<?=e($p['image'])?>" alt="<?=e($p['name'])?>">

                    <div class="cart-details">
                        <h3><?=e($p['name'])?></h3>
                        <p>₹<?=number_format($p['price'], 2)?></p>

                        <form method="POST" class="quantity-form">
                            <input type="hidden" name="csrf"
                                   value="<?=e($_SESSION['csrf'])?>">
                            <input type="hidden" name="product_id"
                                   value="<?=$p['id']?>">
                            <input type="hidden" name="action"
                                   value="update">

                            <input type="number" name="quantity"
                                   min="1" max="<?=min(99, $p['stock'])?>"
                                   value="<?=$qty?>">

                            <button class="small-btn">Update</button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="csrf"
                                   value="<?=e($_SESSION['csrf'])?>">
                            <input type="hidden" name="product_id"
                                   value="<?=$p['id']?>">
                            <input type="hidden" name="action"
                                   value="remove">
                            <button class="remove-btn">Remove</button>
                        </form>
                    </div>

                    <strong>
                        ₹<?=number_format($p['price'] * $qty, 2)?>
                    </strong>

                </div>

                <?php endforeach; ?>

            </div>

            <div class="summary">
                <h3>Order Summary</h3>

                <p>Subtotal
                    <strong>₹<?=number_format($total, 2)?></strong>
                </p>

                <p>Delivery
                    <strong>Free</strong>
                </p>

                <hr>

                <h3>Total: ₹<?=number_format($total, 2)?></h3>

                <a href="checkout.php" class="checkout-btn">
                    Proceed to Checkout
                </a>
            </div>

        </div>

    <?php endif; ?>

</main>

</body>
</html>