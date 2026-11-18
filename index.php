<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $pdo->prepare(
            "SELECT id FROM products WHERE id=? AND status=1 AND stock>0"
        );
        $stmt->execute([$id]);

        if ($stmt->fetch()) {
            $_SESSION['cart'][$id] =
                min(99, ($_SESSION['cart'][$id] ?? 0) + 1);
        }
    }

    header('Location: index.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "SELECT * FROM products WHERE status=1";
$params = [];

if ($search !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}

if ($category !== '') {
    $sql .= " AND category=?";
    $params[] = $category;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT DISTINCT category FROM products WHERE status=1"
)->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messo - Online Grocery Store</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <a href="index.php" class="logo">messo<span>.</span></a>

    <form class="search" method="GET">
        <input type="search" name="search"
               placeholder="Search products..."
               value="<?=e($search)?>">
        <button type="submit">Search</button>
    </form>

    <a href="cart.php" class="cart-btn">
        🛒 Cart (<?=array_sum($_SESSION['cart'])?>)
    </a>
</header>

<nav class="nav">
    <a href="index.php">Home</a>
    <?php foreach ($categories as $cat): ?>
        <a href="?category=<?=urlencode($cat)?>">
            <?=e($cat)?>
        </a>
    <?php endforeach; ?>
</nav>

<section class="hero">
    <div>
        <h1>Freshness Delivered To Your Door</h1>
        <p>Shop premium dry fruits, grocery and daily essentials.</p>
        <a href="#products" class="hero-btn">Shop Now →</a>
    </div>
</section>

<section class="features">
    <div>🚚 Free Delivery</div>
    <div>🌿 Quality Products</div>
    <div>💳 Secure UPI Payment</div>
    <div>📦 Easy Ordering</div>
</section>

<main class="container" id="products">

    <div class="section-heading">
        <div>
            <h2>Popular Products</h2>
            <p>Explore our best-selling products</p>
        </div>
        <a href="index.php">View All →</a>
    </div>

    <div class="product-grid">

        <?php foreach ($products as $p): ?>

        <article class="product-card">

            <a href="#" class="product-image">
                <img src="<?=e($p['image'])?>"
                     alt="<?=e($p['name'])?>"
                     loading="lazy">
            </a>

            <div class="product-info">
                <span class="category">
                    <?=e($p['category'])?>
                </span>

                <h3><?=e($p['name'])?></h3>

                <p class="description">
                    <?=e($p['description'])?>
                </p>

                <div class="price">
                    ₹<?=number_format($p['price'], 2)?>

                    <?php if ($p['old_price']): ?>
                    <del>₹<?=number_format($p['old_price'], 2)?></del>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf"
                           value="<?=e($_SESSION['csrf'])?>">
                    <input type="hidden" name="product_id"
                           value="<?=$p['id']?>">
                    <button class="add-btn" type="submit">
                        + Add to Cart
                    </button>
                </form>
            </div>
        </article>

        <?php endforeach; ?>

        <?php if (!$products): ?>
            <p>No products found.</p>
        <?php endif; ?>

    </div>
</main>

<footer>
    <h2>messo.</h2>
    <p>Fresh products. Better living.</p>
    <p>© <?=date('Y')?> Messo Store. All Rights Reserved.</p>
    <p>Payment Mode: UPI Only</p>
</footer>

</body>
</html>