<?php
// cart.php - Warenkorb anzeigen und verwalten

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/cart_handler.php';

// Verarbeite Aktionen
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'remove') {
            $productID = $_POST['productID'] ?? '';
            if ($productID && remove_from_cart($productID)) {
                $message = 'Artikel aus dem Warenkorb entfernt.';
                $messageType = 'ok';
            }
        } elseif ($_POST['action'] === 'update_quantity') {
            $productID = $_POST['productID'] ?? '';
            $quantity = (int)($_POST['quantity'] ?? 0);
            if ($productID && $quantity > 0 && update_cart_quantity($productID, $quantity)) {
                $message = 'Menge aktualisiert.';
                $messageType = 'ok';
            } elseif ($productID && $quantity <= 0) {
                remove_from_cart($productID);
                $message = 'Artikel entfernt.';
                $messageType = 'ok';
            }
        } elseif ($_POST['action'] === 'clear') {
            clear_cart();
            $message = 'Warenkorb geleert.';
            $messageType = 'ok';
        }
    }
}

$cart = get_cart();
$cartTotal = get_cart_total();
$cartCount = get_cart_count();

include 'header.php';
?>

<?php if ($message): ?>
    <div class="card flash flash-<?php echo $messageType; ?>">
        <strong><?php echo $messageType === 'ok' ? '✓' : '⚠'; ?></strong> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2>🛒 Mein Warenkorb</h2>
    <p>Verwalten Sie die Artikel in Ihrem Warenkorb.</p>
</div>

<?php if (empty($cart)): ?>
    <div class="card" style="text-align:center;padding:40px;">
        <p style="font-size:18px;color:#666;margin:20px 0;">Ihr Warenkorb ist leer</p>
        <a href="/sap_main/products.php" class="btn btn-primary">
            ← Weiter einkaufen
        </a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="cart-table-wrapper">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>Preis</th>
                        <th>Menge</th>
                        <th>Gesamtbetrag</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $item): ?>
                        <?php $itemTotal = $item['price'] * $item['quantity']; ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <br>
                                <small style="color:#666;">ID: <?php echo htmlspecialchars($item['productID']); ?></small>
                            </td>
                            <td>
                                <?php echo number_format($item['price'], 2, ',', '.') . ' ' . htmlspecialchars($item['currency']); ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="action" value="update_quantity">
                                    <input type="hidden" name="productID" value="<?php echo htmlspecialchars($item['productID']); ?>">
                                    <input type="number" 
                                           name="quantity" 
                                           value="<?php echo (int)$item['quantity']; ?>" 
                                           min="1" 
                                           max="999"
                                           style="width:60px;padding:6px;border:1px solid #d1d5db;border-radius:4px;text-align:center;">
                                    <button type="submit" class="btn btn-sm" style="padding:6px 10px;font-size:12px;">Aktualisieren</button>
                                </form>
                            </td>
                            <td>
                                <strong><?php echo number_format($itemTotal, 2, ',', '.') . ' ' . htmlspecialchars($item['currency']); ?></strong>
                            </td>
                            <td>
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="productID" value="<?php echo htmlspecialchars($item['productID']); ?>">
                                    <button type="submit" class="btn btn-link" style="color:#dc2626;">🗑 Entfernen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:24px;padding-top:24px;border-top:2px solid #e5e7eb;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                <div>
                    <p style="margin:0;">Artikel: <strong><?php echo $cartCount; ?></strong></p>
                    <p style="margin:8px 0 0 0;">Unterschiedliche Produkte: <strong><?php echo get_cart_items_count(); ?></strong></p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-size:14px;color:#666;">Gesamtbetrag:</p>
                    <p style="margin:8px 0 0 0;font-size:28px;font-weight:700;color:#2563eb;">
                        <?php echo number_format($cartTotal, 2, ',', '.') . ' EUR'; ?>
                    </p>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <a href="/sap_main/products.php" class="btn btn-link" style="text-decoration:none;">
                    ← Weiter einkaufen
                </a>
                
                <form method="post" style="flex:1;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn" style="background:#9ca3af;color:#fff;width:100%;display:block;">
                        Warenkorb leeren
                    </button>
                </form>
                
                <a href="/sap_main/checkout_cart.php" class="btn btn-primary" style="text-decoration:none;">
                    Zur Kasse →
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
