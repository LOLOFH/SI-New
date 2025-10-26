<?php 
require_once __DIR__.'/session.php';
require_once __DIR__.'/db_settings/auth.php';



// Initialize shopping cart if empty
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
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<header>
    <h1><a href="/index.php">PHP Webshop</a></h1>
    <nav>
        <a href="/products/products.php">Products</a>

        <?php if (!empty($_SESSION['user'])): ?>
             <a href="/order_process/cart.php">
            Checkout
            <?php
                $count = 0;
                if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $item) {
                        $count += is_array($item)
                            ? (int)($item['qty'] ?? $item['quantity'] ?? 1)
                            : (int)$item;
                    }
                }
                if ($count > 0) {
                    echo ' (' . $count . ')';
                }
            ?>
        </a>
        <?php endif; ?>

       

        <?php if (function_exists('is_admin') && is_admin()): ?>
            <a href="/products/archive.php">Archived Products</a>
        <?php endif; ?>

        <?php if (!empty($_SESSION['user'])): ?>
            <a href="/customer/account.php">My Account</a>
            <a href="/login_register/logout.php">Logout</a>
        <?php else: ?>
            <a href="/login_register/register.php">Registration</a>
            <a href="/login_register/login.php">Login</a>
        <?php endif; ?>

        <?php if (empty($_SESSION['user'])): ?>
            <a href="/products/product_add.php">Add product</a>
        <?php endif; ?>

        <?php if (function_exists('is_admin') && is_admin()): ?>
            <a href="/order_process/orders_history.php">All Orders</a>
            <a href="/customer/customers_admin.php">Customer Management</a>
            <a href="/order_process/orders_admin.php">Edit Orders</a>
        <?php endif; ?>
    </nav>
</header>
<main>
