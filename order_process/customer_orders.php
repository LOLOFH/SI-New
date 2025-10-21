<?php
require_once __DIR__ . '/../db_settings/auth.php';
require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db_settings/config.php';

if (!is_admin()) {
    http_response_code(403);
    die('Zugriff verweigert: Adminrechte erforderlich.');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die('Kein Kunde angegeben.'); }

$stmt = $pdo->prepare('SELECT customer_id, name, email FROM customers WHERE customer_id=?');
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { die('Kunde nicht gefunden.'); }

$os = $pdo->prepare('SELECT * FROM orders WHERE customer_id=? ORDER BY order_id DESC');
$os->execute([$id]);
$orders = $os->fetchAll();

include __DIR__ . '/../header.php';
?>
<h2>Admin: Order from <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</h2>

<div class="card">
  <table class="table">
    <tr><th>#</th><th>Date</th><th>Status</th><th>Sum</th><th>Details</th></tr>
    <?php if (!$orders): ?>
      <tr><td colspan="5">No Orders.</td></tr>
    <?php else: foreach ($orders as $o): ?>
      <tr>
        <td><?= (int)$o['order_id'] ?></td>
        <td><?= htmlspecialchars($o['order_date']) ?></td>
        <td><?= htmlspecialchars($o['status']) ?></td>
        <td><?= number_format($o['total_price'],2,',','.') ?> €</td>
        <td><a class="btn-link" href="order_view.php?id=<?= (int)$o['order_id'] ?>">Show</a></td>
      </tr>
    <?php endforeach; endif; ?>
  </table>
  <p><a class="btn-link" href="../customer/customers_admin.php">Back</a></p>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
