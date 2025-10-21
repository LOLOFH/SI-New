<?php
require_once __DIR__ . '/../db_settings/db.php';
include __DIR__ . '/../header.php';

// Alle Produkte laden
$products = $pdo->query('SELECT * FROM products ORDER BY product_id DESC')->fetchAll();
?>
<h2>Products</h2>
<a class="btn" href="product_add.php">+ New Product</a>
<div class="grid">
<?php foreach ($products as $p): ?>
    <div class="card">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
        <p><strong><?= number_format($p['price'], 2, ',', '.') ?> €</strong></p>
        <form method="post" action="../order_process/cart.php?action=add">
            <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
            <input type="number" name="quantity" min="1" value="1" required>
            <?php csrf_field(); ?>
            <button>Add to Basket</button>
        </form>
        <p>
            <a class="btn-link" href="product_delete.php?id=<?= (int)$p['product_id'] ?>" 
               onclick="return confirm('Produkt wirklich löschen?');">Delete</a>
            ·
            <a class="btn-link" href="product_edit.php?id=<?= (int)$p['product_id'] ?>">Edit</a>
        </p>
    </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
