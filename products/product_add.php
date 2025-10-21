<?php
require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../session.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    
    if ($name !== '' && is_numeric($price)) {

        // product_code automatisch generieren (z.B. 10-stellig HEX)
        $product_code = substr(bin2hex(random_bytes(6)), 0, 10);

        $stmt = $pdo->prepare(
            'INSERT INTO products(product_code, name, description, price) VALUES (?,?,?,?)'
        );
        $stmt->execute([$product_code, $name, $desc, $price]);

        $msg = 'Produkt angelegt.';
    } else {
        $msg = 'Bitte Name und Preis angeben.';
    }
}

include __DIR__ . '/../header.php';
?>
<h2>Add Product</h2>
<?php if ($msg): ?><div class="flash flash-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card">
    <?php csrf_field(); ?>
    <label>Name<input name="name" required></label>
    <label>Description<textarea name="description"></textarea></label>
    <label>Price (€)<input type="number" name="price" step="0.01" required></label>
    <button>Save</button>
</form>
<?php include __DIR__ . '/../footer.php'; ?>
