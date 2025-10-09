<?php
require_once 'auth.php';
require_login();
require_once 'db.php';
require_once 'session.php';

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { header('Location: cart.php'); exit; }

// Produkte + Preise laden
$ids = implode(',', array_map('intval', array_keys($cart)));
$rows = $pdo->query("SELECT * FROM products WHERE product_id IN ($ids)")->fetchAll();
$total = 0.0;
foreach ($rows as $p) {
    $total += $cart[$p['product_id']] * (float)$p['price'];
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }

    $pdo->beginTransaction();
    try {
        // Bestellung anlegen
        $stmt = $pdo->prepare('INSERT INTO orders(customer_id,total_price,status) VALUES (?,?,"pending")');
        $stmt->execute([current_user()['customer_id'], $total]);
        $order_id = $pdo->lastInsertId();

        // Positionen anlegen
        $oi = $pdo->prepare('INSERT INTO order_items(order_id,product_id,quantity,price) VALUES (?,?,?,?)');
        foreach ($rows as $p) {
            $oi->execute([$order_id, $p['product_id'], $cart[$p['product_id']], $p['price']]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);
        header('Location: order_view.php?id='.$order_id);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        die('Checkout fehlgeschlagen: '.htmlspecialchars($e->getMessage()));
    }
}

include 'header.php';
?>
<h2>Checkout</h2>
<div class="card">
    <p>Bitte bestätige deine Bestellung.</p>
    <p><strong>Summe: <?= number_format($total,2,',','.') ?> €</strong></p>
    <form method="post">
        <?php csrf_field(); ?>
        <button>Jetzt bestellen</button>
        <a class="btn-link" href="cart.php">Abbrechen</a>
    </form>
</div>
<?php include 'footer.php'; ?>
