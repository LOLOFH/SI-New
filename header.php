<?php 
require_once __DIR__.'/session.php';
require_once __DIR__.'/cart_handler.php';

// Calculate number of items in the cart
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Webshop</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<header class="header-main">
    <div class="header-container">
        <div class="header-left">
            <h1 class="logo"><a href="/index.php">PHP Shop</a></h1>
        </div>
        <nav class="nav-menu">
            <ul class="nav-list">
                <li><a href="/index.php" class="nav-link">Home</a></li>
                <li><a href="/sap_main/products.php" class="nav-link">Products</a></li>
                <li><a href="/pages/contact.php" class="nav-link">Contact</a></li>
            </ul>
        </nav>
        <div class="header-right">
            <a href="/cart.php" class="nav-link checkout-link">
                <span class="cart-icon">🛒</span>
                Cart
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</header>
<main>
