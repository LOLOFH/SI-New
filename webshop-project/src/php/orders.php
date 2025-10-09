<?php
require_once 'db.php';

class Orders {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createOrder($customerId, $totalPrice, $orderItems) {
        $query = "INSERT INTO orders (customer_id, total_price) VALUES (?, ?)";
        $stmt = $this->db->connect()->prepare($query);
        $stmt->execute([$customerId, $totalPrice]);
        $orderId = $this->db->connect()->lastInsertId();

        foreach ($orderItems as $item) {
            $this->addOrderItem($orderId, $item['product_id'], $item['quantity'], $item['price']);
        }

        return $orderId;
    }

    private function addOrderItem($orderId, $productId, $quantity, $price) {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->connect()->prepare($query);
        $stmt->execute([$orderId, $productId, $quantity, $price]);
    }

    public function getOrderHistory($customerId) {
        $query = "SELECT * FROM orders WHERE customer_id = ?";
        $stmt = $this->db->connect()->prepare($query);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($orderId, $status) {
        $query = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = $this->db->connect()->prepare($query);
        $stmt->execute([$status, $orderId]);
    }
}
?>