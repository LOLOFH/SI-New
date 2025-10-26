<?php
require_once __DIR__ . '/../db_settings/db.php';

$id = (int)($_GET['id'] ?? 0);
$msg = '';

if ($id) {
    try {
        // Check if product exists in any completed orders
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE oi.product_id = ? AND o.status = 'completed'
        ");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

       // if ($count > 0) {
            // Archive the product
            $stmt = $pdo->prepare("UPDATE products SET active = 0 WHERE product_id = ?");
            $stmt->execute([$id]);
            $msg = "Product with ID $id has been archived.";

       /* } else {
            // Safe to delete
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->execute([$id]);
            $msg = "Product deleted successfully.";
        }*/

    } catch (PDOException $e) {
        $msg = "Error deleting/archiving product: " . htmlspecialchars($e->getMessage());
    }
}

header('Location: products.php?msg=' . urlencode($msg));
exit;
?>
