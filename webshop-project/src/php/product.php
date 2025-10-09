<?php
require_once 'db.php';

if (!isset($_GET['id'])) {
    echo "<p>No product selected.</p>";
    exit;
}

$product_id = intval($_GET['id']);
$pdo = connect();
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - Product Details</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
    <p><strong>Price:</strong> €<?php echo number_format($product['price'], 2); ?></p>
    <a href="../../../../index.php">Back to Shop</a>
</body>
</html>