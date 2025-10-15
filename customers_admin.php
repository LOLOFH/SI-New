<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'session.php';
require_once 'config.php';

if (!is_admin()) {
    http_response_code(403);
    die('Zugriff verweigert: Adminrechte erforderlich.');
}

$q = trim($_GET['q'] ?? ''); // Name/Email
$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(LOWER(name) LIKE ? OR LOWER(email) LIKE ?)';
    $like = '%'.mb_strtolower($q).'%';
    $params[] = $like; $params[] = $like;
}

$sql = 'SELECT customer_id, name, email, address FROM customers';
if ($where) { $sql .= ' WHERE '.implode(' AND ', $where); }
$sql .= ' ORDER BY customer_id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include 'header.php';
?>
<h2>Admin: Customer-Management</h2>

<div class="card">
  <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>Search (Name/E-Mail)
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="z. B. anna oder anna@example.com">
      </label>
      <div style="align-self:end;">
          <button class="btn">Search</button>
          <a class="btn-link" href="customers_admin.php">Set back </a>
      </div>
  </form>
</div>

<div class="card">
  <table class="table">
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>E-Mail</th>
      <th>Adress</th>
      <th>Actions</th>
    </tr>
    <?php if (!$customers): ?>
      <tr><td colspan="5">No Customer found.</td></tr>
    <?php else: foreach ($customers as $c): ?>
      <tr>
        <td><?= (int)$c['customer_id'] ?></td>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td><?= nl2br(htmlspecialchars($c['address'])) ?></td>
        <td>
          <a class="btn-link" href="customer_orders.php?id=<?= (int)$c['customer_id'] ?>">Orders</a> ·
          <a class="btn-link" href="customer_edit.php?id=<?= (int)$c['customer_id'] ?>">Edit</a> ·
          <a class="btn-link" href="customer_delete.php?id=<?= (int)$c['customer_id'] ?>"
             onclick="return confirm('Kundenkonto wirklich löschen? (Nur möglich ohne Bestellungen)');">Delete</a>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </table>
</div>

<?php include 'footer.php'; ?>
