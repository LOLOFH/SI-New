<?php
require_once 'db.php';

function addOrderItem($orderId, $productId, $quantity, $price) {
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $orderId, $productId, $quantity, $price);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function getOrderItems($orderId) {
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT oi.order_item_id, p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $items;
}

function deleteOrderItem($orderItemId) {
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_item_id = ?");
    $stmt->bind_param("i", $orderItemId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>