<?php
// checkout.php

require_once __DIR__ . '/ErpClient.php';

$erpBaseUrl = 'https://dein-cap-server:4004/rest/api'; // anpassen
$erpToken   = null;

$erp = new ErpClient(
    'http://localhost:4004/rest/api',
    'service-user',
    'service-user'
);


// Wenn GET -> Formular anzeigen
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Produktdaten übergeben? (optional)
    $productID     = $_GET['productID']     ?? '';
    $erpProductUUID= $_GET['erpProductUUID']?? '';
    $price         = $_GET['price']         ?? '';
    $quantity      = $_GET['quantity']      ?? 1;
    ?>
    <!doctype html>
    <html lang="de">
    <head><meta charset="utf-8"><title>Checkout</title></head>
    <body>
        <h1>Checkout</h1>
        <form method="post">
            <h2>Produkt</h2>
            <p>ProductID: <input type="text" name="productID" value="<?php echo htmlspecialchars($productID); ?>" readonly></p>
            <p>ERP Product UUID: <input type="text" name="erpProductUUID" value="<?php echo htmlspecialchars($erpProductUUID); ?>" readonly></p>
            <p>Preis pro Stück: <input type="text" name="price" value="<?php echo htmlspecialchars($price); ?>" readonly></p>
            <p>Menge: <input type="number" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" min="1"></p>

            <h2>Kundendaten</h2>
            <p>Name: <input type="text" name="customerName" required></p>
            <p>E-Mail: <input type="email" name="customerEmail" required></p>
            <p>Straße: <input type="text" name="street" required></p>
            <p>Hausnummer: <input type="text" name="houseNumber" required></p>
            <p>PLZ: <input type="text" name="postalCode" required></p>
            <p>Stadt: <input type="text" name="city" required></p>
            <p>Land/Region (ISO-3): <input type="text" name="country" value="DEU" required></p>

            <button type="submit">Bestellung abschließen</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Ab hier: POST -> Bestellung absenden

$productID      = $_POST['productID']      ?? '';
$erpProductUUID = $_POST['erpProductUUID'] ?? '';
$price          = (float)($_POST['price']  ?? 0);
$quantity       = (int)($_POST['quantity'] ?? 1);

$customerName   = trim($_POST['customerName']  ?? '');
$customerEmail  = trim($_POST['customerEmail'] ?? '');
$street         = trim($_POST['street']        ?? '');
$houseNumber    = trim($_POST['houseNumber']   ?? '');
$postalCode     = trim($_POST['postalCode']    ?? '');
$city           = trim($_POST['city']          ?? '');
$country        = trim($_POST['country']       ?? '');

if (!$productID || !$erpProductUUID || $price <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo "Ungültige Produktdaten.";
    exit;
}

if (!$customerEmail) {
    http_response_code(400);
    echo "E-Mail ist erforderlich.";
    exit;
}

try {
    // 1) Fresh stock check im ERP (optional, aber empfohlen)
    $product = $erp->getProductByProductId($productID);
    if (!$product) {
        throw new RuntimeException("Produkt im ERP nicht gefunden.");
    }
    if ((int)$product['stock'] < $quantity) {
        echo "Nicht genügend Bestand. Verfügbar: " . (int)$product['stock'];
        exit;
    }

    // 2) Kunde im ERP per E-Mail suchen
    $customer = $erp->findCustomerByEmail($customerEmail);

    if (!$customer) {
        // An dieser Stelle müsstest du eigentlich einen neuen Customer im ERP anlegen.
        // Deine aktuelle API bietet dafür (noch) keinen Endpoint.
        // Für die Übung kannst du entweder:
        // - einen vordefinierten "Webshop-Kunden" im ERP nutzen, dessen UUID du hier einträgst, oder
        // - die API um eine createCustomer-Aktion erweitern.
        throw new RuntimeException(
            "Kein ERP-Kunde mit dieser E-Mail gefunden. " .
            "Lege den Kunden im ERP an oder erweitere die API um createCustomer."
        );
    }

    $customerId = $customer['customerID'];

    // 3) Order-Position vorbereiten
    $itemAmount = $price * $quantity;
    $items = [
        [
            'product'    => $erpProductUUID, // UUID aus ERP
            'quantity'   => $quantity,
            'itemAmount' => $itemAmount,
            'currency'   => $product['currency'] ?? 'EUR',
        ],
    ];

    // 4) Order im ERP anlegen (RPC)
    $orderResponse = $erp->createOrder($customerId, $items, $product['currency'] ?? 'EUR');

    $status = $orderResponse['status'];
    $body   = $orderResponse['body'];

    if ($status >= 200 && $status < 300) {
        // Erfolg – createOrder gibt aktuell keinen Body zurück (typisch 204)
        ?>
        <!doctype html>
        <html lang="de">
        <head><meta charset="utf-8"><title>Bestellung erfolgreich</title></head>
        <body>
            <h1>Vielen Dank für Ihre Bestellung!</h1>
            <p>Die Bestellung wurde erfolgreich im ERP-System angelegt.</p>
            <p>Produkt: <?php echo htmlspecialchars($product['name']); ?></p>
            <p>Menge: <?php echo (int)$quantity; ?></p>
            <p>Gesamtbetrag: <?php echo number_format($itemAmount, 2, ',', '.') . ' ' . htmlspecialchars($product['currency']); ?></p>
        </body>
        </html>
        <?php
        exit;
    }

    if ($status === 409) {
        // Konflikt – z.B. nicht genug Bestand (checkProductStock wirft 409)
        echo "Bestellung konnte nicht angelegt werden: Nicht genügend Bestand im ERP.";
        if ($body) {
            echo "<pre>" . htmlspecialchars($body) . "</pre>";
        }
        exit;
    }

    // Andere Fehler
    echo "Fehler beim Anlegen der Bestellung im ERP (HTTP {$status}).";
    if ($body) {
        echo "<pre>" . htmlspecialchars($body) . "</pre>";
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo "Fehler im Checkout: " . htmlspecialchars($e->getMessage());
}
