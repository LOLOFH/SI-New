<?php
require_once __DIR__ . '/../db_settings/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
    $stmt->execute([$id]);
}

header('Location: products.php');
exit;
?>
