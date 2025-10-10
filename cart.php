<?php
require_once 'db.php';
require_once 'session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';

/**
 * Hilfsfunktion: Menge aus einem Cart-Item extrahieren (Array oder Skalar).
 */
function extract_qty($item): int {
    if (is_array($item)) {
        return (int)($item['qty'] ?? $item['quantity'] ?? $item['count'] ?? 1);
    }
    return (int)$item;
}

/**
 * Hilfsfunktion: Warenkorb normalisieren -> [product_id => qty(int>0)]
 */
function normalize_cart(array $cart): array {
    $out = [];
    foreach ($cart as $pid => $val) {
        $pid = (int)$pid;
        $q = max(0, extract_qty($val));
        if ($pid > 0 && $q > 0) {
            $out[$pid] = $q;
        }
    }
    return $out;
}

// Produkt zum Warenkorb hinzufügen
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('csrf_ok') || !csrf_ok()) { die('CSRF ungültig'); }

    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    // Bestehende Menge extrahieren, auch wenn altes Format (Array) vorliegt
    $existing = $_SESSION['cart'][$pid] ?? 0;
    $existingQty = extract_qty($existing);

    $_SESSION['cart'][$pid] = $existingQty + $qty;

    header('Location: cart.php');
    exit;
}

// Produkt aus Warenkorb entfernen
if ($action === 'remove') {
    $pid = (int)($_GET['product_id'] ?? 0);
    unset($_SESSION['cart'][$pid]);
    header('Location: cart.php');
    exit;
}

include 'header.php';

// ---- Warenkorb laden & normalisieren ----
$rawCart = $_SESSION['cart'] ?? [];
$cart = normalize_cart(is_array($rawCart) ? $rawCart : []);

// ---- Produkte laden & Summen berechnen ----
$total = 0.0;
$items = [];

if (!empty($cart)) {
    // Sichere Prepared-Statement Abfrage
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT product_id, name, price FROM products WHERE product_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $p) {
        $pid = (int)$p['product_id'];
        $q   = (int)($cart[$pid] ?? 0);
        $price = (float)$p['price'];
        $line  = $q * $price;       // <- $q ist jetzt sicher ein int, keine Arrays mehr
        $total += $line;
        $items[] = ['p' => $p, 'qty' => $q, 'line' => $line];
    }
}
?>
<h2>Warenkorb</h2>
<div class="card">
<?php if (!$items): ?>
    <p>Dein Warenkorb ist leer.</p>
<?php else: ?>
    <table class="table">
        <tr>
            <th>Produkt</th>
            <th>Menge</th>
            <th>Preis</th>
            <th>Gesamt</th>
            <th></th>
        </tr>
        <?php foreach($items as $it): $p = $it['p']; ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= (int)$it['qty'] ?></td>
                <td><?= number_format((float)$p['price'], 2, ',', '.') ?> €</td>
                <td><?= number_format((float)$it['line'], 2, ',', '.') ?> €</td>
                <td>
                    <a class="btn-link" href="cart.php?action=remove&product_id=<?= (int)$p['product_id'] ?>">
                        Entfernen
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p><strong>Summe: <?= number_format((float)$total, 2, ',', '.') ?> €</strong></p>
    <a class="btn" href="checkout.php">Zur Kasse</a>
<?php endif; ?>
</div>
<?php include 'footer.php'; ?>
