<?php
// cart.php - View and manage the cart

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
                $message = 'Item removed from cart.';
                $messageType = 'ok';
            }
        } elseif ($_POST['action'] === 'update_quantity') {
            $productID = $_POST['productID'] ?? '';
            $quantity = (int)($_POST['quantity'] ?? 0);
            if ($productID && $quantity > 0 && update_cart_quantity($productID, $quantity)) {
                $message = 'Quantity updated.';
                $messageType = 'ok';
            } elseif ($productID && $quantity <= 0) {
                remove_from_cart($productID);
                $message = 'Item removed.';
                $messageType = 'ok';
            }
        } elseif ($_POST['action'] === 'clear') {
            clear_cart();
            $message = 'Cart cleared.';
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
    <h2>🛒 My Cart</h2>
    <p>Manage the items in your cart.</p>
</div>

<?php if (empty($cart)): ?>
    <div class="card" style="text-align:center;padding:40px;">
        <p style="font-size:18px;color:#666;margin:20px 0;">Your cart is empty</p>
        <a href="/sap_main/products.php" class="btn btn-primary">
            ← Continue shopping
        </a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="cart-table-wrapper">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
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
                                    <button type="submit" class="btn btn-sm" style="padding:6px 10px;font-size:12px;">Update</button>
                                </form>
                            </td>
                            <td>
                                <strong><?php echo number_format($itemTotal, 2, ',', '.') . ' ' . htmlspecialchars($item['currency']); ?></strong>
                            </td>
                            <td>
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="productID" value="<?php echo htmlspecialchars($item['productID']); ?>">
                                    <button type="submit" class="btn btn-link" style="color:#dc2626;">🗑 Remove</button>
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
                    <p style="margin:0;">Items: <strong><?php echo $cartCount; ?></strong></p>
                    <p style="margin:8px 0 0 0;">Unique products: <strong><?php echo get_cart_items_count(); ?></strong></p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-size:14px;color:#666;">Total:</p>
                    <p style="margin:8px 0 0 0;font-size:28px;font-weight:700;color:#2563eb;">
                        <?php echo number_format($cartTotal, 2, ',', '.') . ' EUR'; ?>
                    </p>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <a href="/sap_main/products.php" class="btn btn-link" style="text-decoration:none;">
                    ← Continue shopping
                </a>
                
                <form method="post" style="flex:1;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn" style="background:#9ca3af;color:#fff;width:100%;display:block;">
                        Clear cart
                    </button>
                </form>
                
                <a href="/sap_main/checkout_cart.php" class="btn btn-primary" style="text-decoration:none;">
                    Checkout →
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
