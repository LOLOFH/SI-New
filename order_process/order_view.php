<?php
require_once __DIR__ . '/../db_settings/auth.php';
require_login();
require_once __DIR__ . '/../db_settings/db.php';

$id = (int)($_GET['id'] ?? 0);
$user = current_user();

$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_id=? AND customer_id=?');
$stmt->execute([$id, $user['customer_id']]);
$order = $stmt->fetch();
if (!$order) { die('Bestellung nicht gefunden'); }

$stmt = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

include __DIR__ . '/../header.php';
?>
<h2>Order #<?= (int)$order['order_id'] ?></h2>

<div class="card">
    <p>Status: <strong><?= htmlspecialchars($order['status']) ?></strong></p>
    <p>Date: <?= htmlspecialchars($order['order_date']) ?></p>
    <p>Sum: <strong><?= number_format($order['total_price'], 2, ',', '.') ?> €</strong></p>
</div>

<div class="card">
<table class="table">
    <tr><th>Produkt</th><th>Menge</th><th>Einzelpreis</th><th>Gesamt</th></tr>
    <?php foreach ($items as $it): ?>
        <tr>
            <td><?= htmlspecialchars($it['name']) ?></td>
            <td><?= (int)$it['quantity'] ?></td>
            <td><?= number_format($it['price'], 2, ',', '.') ?> €</td>
            <td><?= number_format($it['price'] * $it['quantity'], 2, ',', '.') ?> €</td>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
