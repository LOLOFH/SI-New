<?php
require_once 'db.php';

// Produkt hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);

    $pdo = connect();
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $price]);
}

// Produkt löschen
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $pdo = connect();
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
}

// Produkte anzeigen
$pdo = connect();
$stmt = $pdo->query("SELECT * FROM products ORDER BY product_id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($products) === 0) {
    echo "<p>No products available.</p>";
} else {
    echo '<table class="product-table">';
    echo '<tr><th>Name</th><th>Description</th><th>Price (€)</th><th>Action</th></tr>';
    foreach ($products as $product) {
        echo '<tr>';
        echo '<td>
        <a href="webshop-project/src/php/product.php?id=' . $product['product_id'] . '">'
        . htmlspecialchars($product['name']) .
        '</a>
    </td>';
        echo '<td>' . htmlspecialchars($product['description']) . '</td>';
        echo '<td>' . number_format($product['price'], 2) . '</td>';
        echo '<td>
            <a href="?delete=' . $product['product_id'] . '" onclick="return confirm(\'Delete this product?\')" class="delete-btn">
                <i class="fa fa-trash"></i> Delete
            </a>
        </td>';
        echo '</tr>';
    }
    echo '</table>';
}
?>