<?php
// products.php

require_once __DIR__ . '/ErpClient.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cart_handler.php';

// Adjust ERP configuration:
$erpBaseUrl = 'http://localhost:4004/rest/api';

// Wenn du (noch) keine Authentifizierung im CAP-Service nutzt:
$erpToken = null;

// Wenn du einen Bearer-Token o.ä. hast, trage ihn hier ein:
// $erpToken = 'DEIN_TOKEN_HIER';

$erp = new ErpClient(
    'http://localhost:4004/rest/api',
    'service-user',
    'service-user'
);

// Handle "Add to cart" request
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productID = $_POST['productID'] ?? '';
    $erpProductUUID = $_POST['erpProductUUID'] ?? '';
    $name = $_POST['product_name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $currency = $_POST['currency'] ?? 'EUR';
    $quantity = $_POST['quantity'] ?? 1;
    
    if ($productID && $erpProductUUID && $name && $price > 0) {
        add_to_cart($productID, $erpProductUUID, $name, $price, $currency, $quantity);
        $successMessage = htmlspecialchars($name) . ' has been added to your cart!';
    }
}

// Echtzeit aus ERP laden (ERP = Single Source of Truth)
try {
    $products = $erp->getProducts();
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error loading products from ERP: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<?php include '../header.php'; ?>

<?php if ($successMessage): ?>
    <div class="card flash flash-ok" style="margin-top:0;">
        <strong>✓ Erfolg:</strong> <?php echo $successMessage; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2>📋 Products</h2>
    <p>Select a product and add it to your cart.</p>
</div>

<?php if (empty($products)): ?>
    <div class="card">
        <p>No products found in ERP.</p>
    </div>
<?php else: ?>
    <div class="grid">
    <?php foreach ($products as $p): ?>
        <?php
            $stock    = (int)($p['stock'] ?? 0);
            $inStock  = $stock > 0;
            $price    = (float)($p['price'] ?? 0);
            // In deinem Service heißt das Feld currency_code:
            $currency = $p['currency_code'] ?? ($p['currency'] ?? 'EUR');
        ?>
        <div class="product-card">
            <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
            
            <p class="product-description"><?php echo htmlspecialchars($p['description'] ?? ''); ?></p>
            
            <div class="product-price">
                <?php echo number_format($price, 2, ',', '.') . ' ' . htmlspecialchars($currency); ?>
            </div>
            
            <div class="product-stock">
                <?php if ($inStock): ?>
                    <span class="stock-available">✓ Available (<?php echo $stock; ?> in stock)</span>
                <?php else: ?>
                    <span class="stock-unavailable">✗ Not available</span>
                <?php endif; ?>
            </div>
            
            <?php if ($inStock): ?>
                <!-- Add to cart -->
                <form method="post" class="product-form">
                    <input type="hidden" name="add_to_cart" value="1">
                    <input type="hidden" name="productID" value="<?php echo htmlspecialchars($p['productID']); ?>">
                    <input type="hidden" name="erpProductUUID" value="<?php echo htmlspecialchars($p['ID']); ?>">
                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($p['name']); ?>">
                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                    <input type="hidden" name="currency" value="<?php echo htmlspecialchars($currency); ?>">
                    
                    <div class="form-group" style="margin-bottom:12px;">
                        <label for="qty-<?php echo htmlspecialchars($p['productID']); ?>" style="display:inline-block;margin-right:8px;margin-bottom:0;">Quantity:</label>
                        <input type="number"
                               id="qty-<?php echo htmlspecialchars($p['productID']); ?>"
                               name="quantity"
                               min="1"
                               max="<?php echo $stock; ?>"
                               value="1"
                               style="width:70px;padding:6px;border:1px solid #d1d5db;border-radius:4px;">
                    </div>
                    
                    <button type="submit" class="add-to-cart-btn">
                        🛒 Add to cart
                    </button>
                </form>
            <?php else: ?>
                <button type="button" class="add-to-cart-btn" disabled style="background:#9ca3af;cursor:not-allowed;">
                    Not available
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../footer.php'; ?>
