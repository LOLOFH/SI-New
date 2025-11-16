<?php
// products.php

require_once __DIR__ . '/ErpClient.php';

// ERP-Konfiguration anpassen:
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


// Echtzeit aus ERP laden (ERP = Single Source of Truth)
try {
    $products = $erp->getProducts();
} catch (Throwable $e) {
    http_response_code(500);
    echo "Fehler beim Laden der Produkte aus dem ERP: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Produktübersicht</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        th { background: #eee; }
        .instock { color: green; font-weight: bold; }
        .outofstock { color: red; font-weight: bold; }
        form { margin: 0; }
    </style>
</head>
<body>
    <h1>Produkte</h1>

    <?php if (empty($products)): ?>
        <p>Keine Produkte im ERP gefunden.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Name</th>
                    <th>Beschreibung</th>
                    <th>Preis</th>
                    <th>Lager</th>
                    <th>Bestellen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <?php
                    $stock    = (int)($p['stock'] ?? 0);
                    $inStock  = $stock > 0;
                    $price    = (float)($p['price'] ?? 0);
                    // In deinem Service heißt das Feld currency_code:
                    $currency = $p['currency_code'] ?? ($p['currency'] ?? 'EUR');
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['productID']); ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                    <td><?php echo number_format($price, 2, ',', '.') . ' ' . htmlspecialchars($currency); ?></td>
                    <td>
                        <?php if ($inStock): ?>
                            <span class="instock">in stock (<?php echo $stock; ?>)</span>
                        <?php else: ?>
                            <span class="outofstock">out of stock</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($inStock): ?>
                            <!-- Bestellung von dieser Seite starten -->
                            <form method="get" action="checkout.php">
                                <input type="hidden" name="productID" value="<?php echo htmlspecialchars($p['productID']); ?>">
                                <input type="hidden" name="erpProductUUID" value="<?php echo htmlspecialchars($p['ID']); ?>">
                                <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                                <input type="number"
                                       name="quantity"
                                       min="1"
                                       max="<?php echo $stock; ?>"
                                       value="1"
                                       style="width:60px;">
                                <button type="submit">Bestellen</button>
                            </form>
                        <?php else: ?>
                            <button type="button" disabled>Nicht verfügbar</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
