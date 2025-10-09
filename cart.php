<?php
require_once 'db.php';
require_once 'session.php';

$action = $_GET['action'] ?? '';

// Produkt zum Warenkorb hinzufügen
if ($action==='add' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
    header('Location: cart.php');
    exit;
}

// Produkt aus Warenkorb entfernen
if ($action==='remove') {
    $pid = (int)($_GET['product_id'] ?? 0);
    unset($_SESSION['cart'][$pid]);
    header('Location: cart.php');
    exit;
}

include 'header.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0.0;
$items = [];

if ($cart) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $rows = $pdo->query("SELECT * FROM products WHERE product_id IN ($ids)")->fetchAll();
    foreach ($rows as $p) {
        $q = $cart[$p['product_id']] ?? 0;
        $line = $q * (float)$p['price'];
        $total += $line;
        $items[] = ['p'=>$p,'qty'=>$q,'line'=>$line];
    }
}
?>
<h2>Warenkorb</h2>
<div class="card">
<?php if (!$items): ?>
    <p>Dein Warenkorb ist leer.</p>
<?php else: ?>
    <table class="table">
        <tr><th>Produkt</th><th>Menge</th><th>Preis</th><th>Gesamt</th><th></th></tr>
        <?php foreach($items as $it): $p=$it['p']; ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= (int)$it['qty'] ?></td>
                <td><?= number_format($p['price'],2,',','.') ?> €</td>
                <td><?= number_format($it['line'],2,',','.') ?> €</td>
                <td><a class="btn-link" href="cart.php?action=remove&product_id=<?= (int)$p['product_id'] ?>">Entfernen</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p><strong>Summe: <?= number_format($total,2,',','.') ?> €</strong></p>
    <a class="btn" href="checkout.php">Zur Kasse</a>
<?php endif; ?>
</div>
<?php include 'footer.php'; ?>
