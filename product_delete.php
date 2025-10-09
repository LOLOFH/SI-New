<?php
require_once 'db.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM products WHERE product_id=?');
    $stmt->execute([$id]);
}
header('Location: products.php');
exit;
?>
