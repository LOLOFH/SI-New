<?php 
require_once __DIR__.'/session.php';
require_once __DIR__.'/auth.php'; // <- wichtig für is_admin() / is_logged_in()
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
        <a href="products.php">Produkte</a>
        <a href="cart.php">
            Warenkorb<?php
                $count = 0; 
                if (!empty($_SESSION['cart'])) { 
                    foreach ($_SESSION['cart'] as $q) { $count += $q; } 
                }
                if ($count) echo ' ('.$count.')';
            ?>
        </a>

        <?php if (!empty($_SESSION['user'])): ?>
            <a href="account.php">Mein Konto</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="register.php">Registrieren</a>
            <a href="login.php">Login</a>
        <?php endif; ?>

        <a href="product_add.php">Produkt hinzufügen</a>

        <?php if (function_exists('is_admin') && is_admin()): ?>
            <a href="orders_history.php">Alle Bestellungen</a>
        <?php endif; ?>
        <?php if (function_exists('is_admin') && is_admin()): ?>
    <a href="customers_admin.php">Kunden verwalten</a>
    <a href="orders_admin.php">Bestellungen verwalten</a>
<?php endif; ?>

    </nav>
</header>
<main>
