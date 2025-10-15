<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'session.php';
require_once 'config.php';

// Nur Admins dürfen diese Seite sehen (Gast-Admin möglich, wenn DEV_ALLOW_GUEST_ADMIN = true)
if (!is_admin()) {
    http_response_code(403);
    die('Zugriff verweigert: Adminrechte erforderlich.');
}

// --- Filter einlesen ---
$q       = trim($_GET['q'] ?? '');                // Name oder E-Mail
$status  = $_GET['status'] ?? '';                 // pending|shipped|completed
$date_from = trim($_GET['from'] ?? '');           // YYYY-MM-DD
$date_to   = trim($_GET['to'] ?? '');             // YYYY-MM-DD

$where = [];
$params = [];

// Suche in Name/Email
if ($q !== '') {
    $where[] = '(LOWER(c.name) LIKE ? OR LOWER(c.email) LIKE ?)';
    $like = '%'.mb_strtolower($q).'%';
    $params[] = $like;
    $params[] = $like;
}
// Status
$valid_status = ['pending','shipped','completed'];
if ($status !== '' && in_array($status, $valid_status, true)) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
// Datum von
if ($date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $where[] = 'DATE(o.order_date) >= ?';
    $params[] = $date_from;
}
// Datum bis
if ($date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $where[] = 'DATE(o.order_date) <= ?';
    $params[] = $date_to;
}

$sql = '
    SELECT 
        o.order_id, o.order_date, o.total_price, o.status,
        c.customer_id, c.name AS customer_name, c.email AS customer_email,
        (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.order_id = o.order_id) AS items_count
    FROM orders o
    JOIN customers c ON c.customer_id = o.customer_id
';
if ($where) {
    $sql .= ' WHERE '.implode(' AND ', $where);
}
$sql .= ' ORDER BY o.order_id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include 'header.php';
?>
<h2>Admin: Order-History (all User)</h2>

<div class="card">
  <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
      <label>Search (Name/E-Mail)
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="z. B. anna oder anna@example.com">
      </label>
      <label>Status
          <select name="status">
              <option value="">all</option>
              <?php foreach (['pending','shipped','completed'] as $st): ?>
                  <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= $st ?></option>
              <?php endforeach; ?>
          </select>
      </label>
      <label>from (YYYY-MM-DD)
          <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>">
      </label>
      <label>till (YYYY-MM-DD)
          <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>">
      </label>
      <div style="align-self:end;">
          <button class="btn">Filter</button>
          <a class="btn-link" href="orders_history.php">remove</a>
      </div>
  </form>
</div>

<div class="card">
  <table class="table">
      <tr>
          <th>#</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Article</th>
          <th>Sum</th>
          <th>Status</th>
          <th>Details</th>
      </tr>
      <?php if (!$orders): ?>
          <tr><td colspan="7">No Order found.</td></tr>
      <?php else: ?>
          <?php foreach ($orders as $o): ?>
              <tr>
                  <td><?= (int)$o['order_id'] ?></td>
                  <td><?= htmlspecialchars($o['order_date']) ?></td>
                  <td>
                      <?= htmlspecialchars($o['customer_name']) ?><br>
                      <small><?= htmlspecialchars($o['customer_email']) ?></small>
                  </td>
                  <td><?= (int)$o['items_count'] ?></td>
                  <td><?= number_format($o['total_price'],2,',','.') ?> €</td>
                  <td><?= htmlspecialchars($o['status']) ?></td>
                  <td><a class="btn-link" href="order_view.php?id=<?= (int)$o['order_id'] ?>">Show</a></td>
              </tr>
          <?php endforeach; ?>
      <?php endif; ?>
  </table>
</div>

<?php include 'footer.php'; ?>
