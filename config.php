<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'messo_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPI_ID', 'yourupi@bank');
define('UPI_NAME', 'Messo Store');

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die("Database connection failed.");
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_check() {
    if (
        !isset($_POST['csrf']) ||
        !hash_equals($_SESSION['csrf'], $_POST['csrf'])
    ) {
        http_response_code(403);
        exit('Invalid request.');
    }
}

function getCartTotal($pdo) {
    $total = 0;

    foreach ($_SESSION['cart'] as $id => $qty) {
        $stmt = $pdo->prepare(
            "SELECT price FROM products WHERE id=? AND status=1"
        );
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $total += $product['price'] * $qty;
        }
    }

    return $total;
}
?>