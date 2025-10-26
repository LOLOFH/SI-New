<?php
require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../session.php';
include __DIR__ . '/../header.php';

$msg = '';

// Handle restore action
if (isset($_POST['restore'])) {
    $id = (int)$_POST['product_id'];
    $stock = (int)($_POST['stock'] ?? 0);

    if ($id) {
            // Update stock and restore
            $stmt = $pdo->prepare("UPDATE products SET stock = ?, active = 1 WHERE product_id = ?");
            if ($stmt->execute([$stock, $id])) {
                $msg = "Product with ID $id restored with stock $stock.";
            } else {
                $msg = "Error restoring product.";
            }
    }

    header('Location: archive.php?msg=' . urlencode($msg));
    exit;
}

// Fetch archived products
$stmt = $pdo->query("SELECT * FROM products WHERE active = 0 ORDER BY name ASC");
$archivedProducts = $stmt->fetchAll();
?>

<?php if (!empty($_GET['msg'])): ?>
    <p style="color:green;"><?= htmlspecialchars($_GET['msg']) ?></p>
<?php endif; ?>

<?php if (count($archivedProducts) === 0): ?>
    <p>No archived products.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($archivedProducts as $p): ?>
            <div class="card">
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                <p><strong><?= number_format((float)$p['price'], 2, ',', '.') ?> €</strong></p>
                <p><strong>Stock:</strong> <?= (int)$p['stock'] ?></p>

                <!-- Restore form -->
                <form method="post" onsubmit="return confirm('Really restore this product?');">
                    <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                    
                        <label>Stock:
                            <input type="number" name="stock" min="1" value="<?= (int)$p['stock'] ?>" required>
                        </label>
                    <button type="submit" name="restore">Restore</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../footer.php'; ?>
