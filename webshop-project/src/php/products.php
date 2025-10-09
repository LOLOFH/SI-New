<?php
require_once 'db.php';

function getProducts() {
    $conn = dbConnect();
    $sql = "SELECT * FROM products";
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $conn->close();
    return $products;
}

function addProduct($name, $description, $price) {
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $description, $price);
    
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function deleteProduct($product_id) {
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>