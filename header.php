<?php 
require_once __DIR__.'/session.php';
require_once __DIR__.'/auth.php'; // wichtig für is_admin() / is_logged_in()

// Session starten, falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Warenkorb initialisieren, falls leer
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Webshop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1><a href="index.php">PHP Webshop</a></h1>
    <nav>
        <a href="products.php">Products</a>
        <a href="cart.php">
            Checkout
            <?php
                $count = 0;

                if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $item) {
                        if (is_array($item)) {
                            // gängige Keys wie 'qty' oder 'quantity' berücksichtigen, sonst 1
                            $count += (int)($item['qty'] ?? $item['quantity'] ?? 1);
                        } else {
                            // falls nur Mengenwerte gespeichert werden
                            $count += (int)$item;
                        }
                    }
                }

                if ($count > 0) {
                    echo ' (' . $count . ')';
                }
            ?>
        </a>

        <?php if (!empty($_SESSION['user'])): ?>
            <a href="account.php">My Konto</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="register.php">Registration</a>
            <a href="login.php">Login</a>
        <?php endif; ?>

        <a href="product_add.php">Add Product</a>

        <?php if (function_exists('is_admin') && is_admin()): ?>
            <a href="orders_history.php">All Orders</a>
            <a href="customers_admin.php">Customer Management</a>
            <a href="orders_admin.php">Edit Orders</a>
        <?php endif; ?>
    </nav>
</header>
<main>
