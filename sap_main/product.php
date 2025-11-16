<?php
// product.php

require_once __DIR__ . '/ErpClient.php';

// Konfiguration
$erpBaseUrl = 'https://dein-cap-server:4004/rest/api'; // anpassen
$erpToken   = null;                                    // falls nötig

$erp = new ErpClient($erpBaseUrl, $erpToken);

// ProductID aus URL
$productId = $_GET['product'] ?? null;
if (!$productId) {
    http_response_code(400);
    echo "Missing product parameter.";
    exit;
}

try {
    // Echtzeit-Call ins ERP
    $product = $erp->getProductByProductId($productId);

    if (!$product) {
        http_response_code(404);
        echo "Product not found in ERP.";
        exit;
    }

    $inStock = isset($product['stock']) && $product['stock'] > 0;
} catch (Throwable $e) {
    // Fehler beim RPC – hier keine veralteten Daten verwenden
    http_response_code(500);
    echo "Error contacting ERP: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>
    <p>Preis: <?php echo htmlspecialchars($product['price'] . ' ' . $product['currency']); ?></p>

    <p>
        Lagerbestand:
        <?php if ($inStock): ?>
            <strong style="color:green;">in stock (<?php echo (int)$product['stock']; ?>)</strong>
        <?php else: ?>
            <strong style="color:red;">out of stock</strong>
        <?php endif; ?>
    </p>

    <?php if ($inStock): ?>
        <form method="post" action="checkout.php">
            <input type="hidden" name="productID" value="<?php echo htmlspecialchars($product['productID']); ?>">
            <input type="hidden" name="erpProductUUID" value="<?php echo htmlspecialchars($product['ID']); ?>">
            <input type="hidden" name="price" value="<?php echo htmlspecialchars($product['price']); ?>">
            <input type="number" name="quantity" min="1" max="<?php echo (int)$product['stock']; ?>" value="1">
            <button type="submit">Jetzt kaufen</button>
        </form>
    <?php else: ?>
        <p>Dieses Produkt ist aktuell nicht verfügbar.</p>
    <?php endif; ?>
</body>
</html>
