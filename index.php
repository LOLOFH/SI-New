<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webshop</title>
    <link rel="stylesheet" href="webshop-project/src/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <?php include 'webshop-project/src/templates/header.php'; ?>

    <main class="container">
        <section class="hero">
            <h1><i class="fa fa-store"></i> Welcome to Our Webshop</h1>
            <p>Discover great products and enjoy a seamless shopping experience!</p>
        </section>

        <!-- Product Management -->
        <section class="card">
            <h2><i class="fa fa-box"></i> Products</h2>
            <form class="product-form" action="" method="POST">
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="text" name="description" placeholder="Description" required>
                <input type="number" name="price" placeholder="Price (€)" step="0.01" required>
                <button type="submit" name="add_product"><i class="fa fa-plus"></i> Add Product</button>
            </form>
            <div class="product-list">
                <?php include 'webshop-project/src/php/products.php'; ?>
            </div>
        </section>

        <!-- Customer Management -->
        <section class="card">
            <h2><i class="fa fa-user"></i> Customer Account</h2>
            <div class="customer-area">
                <?php include 'webshop-project/src/php/customers.php'; ?>
            </div>
        </section>

        <!-- Order Processing -->
        <section class="card">
            <h2><i class="fa fa-shopping-cart"></i> Your Cart / Orders</h2>
            <div class="order-area">
                <?php include 'webshop-project/src/php/orders.php'; ?>
            </div>
        </section>
    </main>

    <?php include 'webshop-project/src/templates/footer.php'; ?>
</body>
</html>