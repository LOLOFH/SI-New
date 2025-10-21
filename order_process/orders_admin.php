<?php
require_once __DIR__ . '/../db_settings/auth.php';
require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db_settings/config.php';

// Admin-Check (Gast darf Admin sein, wenn DEV_ALLOW_GUEST_ADMIN = true)
if (!is_admin()) {
    http_response_code(403);
    die('Zugriff verweigert: Adminrechte erforderlich.');
}

// ... Rest der Datei unverändert ...

// Status-Update verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = $_POST['status'] ?? 'pending';
    if ($order_id && in_array($status, ['pending','shipped','completed'], true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status=? WHERE order_id=?');
        $stmt->execute([$status, $order_id]);
        header('Location: orders_admin.php?ok=1');
        exit;
    }
}

// Bestellungen + Kunde laden
$q = $pdo->query('
    SELECT o.order_id, o.order_date, o.total_price, o.status,
           c.name AS customer_name, c.email AS customer_email
    FROM orders o
    JOIN customers c ON c.customer_id = o.customer_id
    ORDER BY o.order_id DESC
');
$orders = $q->fetchAll();

include __DIR__ . '/../header.php';
?>
<h2>Admin: Orders</h2>
<?php if (!empty($_GET['ok'])): ?>
  <div class="flash flash-ok">Status updated.</div>
<?php endif; ?>

<div class="card">
  <table class="table">
    <tr>
      <th>#</th>
      <th>Date</th>
      <th>Customer</th>
      <th>Sum</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= (int)$o['order_id'] ?></td>
        <td><?= htmlspecialchars($o['order_date']) ?></td>
        <td>
          <?= htmlspecialchars($o['customer_name']) ?><br>
          <small><?= htmlspecialchars($o['customer_email']) ?></small>
        </td>
        <td><?= number_format($o['total_price'],2,',','.') ?> €</td>
        <td>
          <form method="post" style="display:flex; gap:8px; align-items:center;">
            <?php csrf_field(); ?>
            <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
            <select name="status">
              <?php foreach (['pending','shipped','completed'] as $st): ?>
                <option value="<?= $st ?>" <?= $o['status']===$st?'selected':'' ?>>
                  <?= $st ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn">Save</button>
          </form>
        </td>
        <td><a class="btn-link" href="order_view.php?id=<?= (int)$o['order_id'] ?>">Details</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
