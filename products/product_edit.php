<?php
require_once __DIR__ . '/../db_settings/auth.php';
require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db_settings/config.php';

// Admin-Check
if (!is_admin()) {
    http_response_code(403);
    die('Zugriff verweigert: Adminrechte erforderlich.');
}

$id  = (int)($_GET['id'] ?? 0);
if (!$id) { die('Kein Produkt angegeben.'); }

// Produkt laden
$stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { die('Produkt nicht gefunden.'); }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';

    if ($name !== '' && is_numeric($price) && is_numeric($stock)) {
        $upd = $pdo->prepare('UPDATE products SET name=?, description=?, price=?, stock=? WHERE product_id=?');
        $upd->execute([$name, $desc, $price, (int)$stock, $id]);
        $msg = 'Produkt aktualisiert.';
        // Neu laden
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    } else {
        $msg = 'Bitte Name, Preis und gültigen Lagerbestand angeben.';
    }
}

include __DIR__ . '/../header.php';
?>
<h2>Edit Product</h2>
<?php if ($msg): ?>
  <div class="flash <?= str_starts_with($msg,'Bitte') ? 'flash-err' : 'flash-ok' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<form method="post" class="card">
    <?php csrf_field(); ?>
    <label>Name
      <input name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    </label>
    <label>Description
      <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
    </label>
    <label>Price (€)
      <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
    </label>
    <label>Stock
      <input type="number" name="stock" min="0" value="<?= htmlspecialchars($product['stock']) ?>" required>
    </label>
    <div style="display:flex; gap:12px; align-items:center; margin-top:8px;">
      <button class="btn">Save</button>
      <a class="btn-link" href="products.php">Back</a>
    </div>
</form>
<?php include __DIR__ . '/../footer.php'; ?>
