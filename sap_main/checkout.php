<?php
// checkout.php

require_once __DIR__ . '/ErpClient.php';

// ERP-Client mit Basic Auth
$erp = new ErpClient(
    'http://localhost:4004/rest/api', // ggf. anpassen
    'service-user',
    'service-user'
);

// Wenn GET -> Formular anzeigen
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Produktdaten übergeben
    $productID       = $_GET['productID']      ?? '';
    $erpProductUUID  = $_GET['erpProductUUID'] ?? '';
    $price           = $_GET['price']          ?? '';
    $quantity        = $_GET['quantity']       ?? 1;

    // Kunden aus ERP laden
    try {
        $customers = $erp->getCustomers();
    } catch (Throwable $e) {
        http_response_code(500);
        echo "Fehler beim Laden der Kunden aus dem ERP: " . htmlspecialchars($e->getMessage());
        exit;
    }
    ?>
    <!doctype html>
    <html lang="de">
    <head>
        <meta charset="utf-8">
        <title>Checkout</title>
        <style>
            body { font-family: Arial, sans-serif; }
            label { display: inline-block; width: 160px; }
            input[type="text"],
            input[type="number"],
            select { width: 250px; }
            details { margin-top: 16px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
            details > summary { cursor: pointer; font-weight: bold; }
            details[open] { background-color: #f9f9f9; }
            .hint { font-size: 0.9em; color: #666; }
        </style>
    </head>
    <body>
        <h1>Checkout</h1>
        <form method="post">
            <h2>Produkt</h2>
            <p>
                <label>ProductID:</label>
                <input type="text" name="productID"
                       value="<?php echo htmlspecialchars($productID); ?>" readonly>
            </p>
            <p>
                <label>ERP Product UUID:</label>
                <input type="text" name="erpProductUUID"
                       value="<?php echo htmlspecialchars($erpProductUUID); ?>" readonly>
            </p>
            <p>
                <label>Preis pro Stück:</label>
                <input type="text" name="price"
                       value="<?php echo htmlspecialchars($price); ?>" readonly>
            </p>
            <p>
                <label>Menge:</label>
                <input type="number" name="quantity"
                       value="<?php echo htmlspecialchars($quantity); ?>" min="1">
            </p>

            <h2>Kunde auswählen</h2>
            <?php if (empty($customers)): ?>
                <p>Es sind keine Kunden im ERP vorhanden. Bitte zuerst Kunden im ERP anlegen.</p>
            <?php else: ?>
                <p>
                    <label for="customerEmail">Kunde (E-Mail):</label>
                    <select name="customerEmail" id="customerEmail" required>
                        <option value="">-- bitte wählen --</option>
                        <?php foreach ($customers as $c): ?>
                            <?php
                                $email = $c['email'] ?? '';
                                $name  = $c['name']  ?? '';
                                if (!$email) {
                                    continue;
                                }
                            ?>
                            <option value="<?php echo htmlspecialchars($email); ?>">
                                <?php echo htmlspecialchars($email . ($name ? ' - ' . $name : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
            <?php endif; ?>

            <!-- Einklappbarer Bereich: alte Methode / Kunde anlegen (konzeptionell) -->
            <details>
                <summary>Alternative: neuen Kunden anlegen (zukünftig)</summary>
                <p class="hint">
                    Idee: Hier könnte ein neuer Kunde direkt aus dem Webshop im ERP angelegt werden.
                    Dafür wäre eine passende API-Funktion (z.&nbsp;B. <code>createCustomer</code>) im
                    SimpleERPApi nötig. Diese Felder zeigen nur, dass dieser Use Case vorgesehen ist.
                </p>
                <p><label>Name:</label> <input type="text" name="newCustomerName"></p>
                <p><label>E-Mail:</label> <input type="email" name="newCustomerEmail"></p>
                <p><label>Straße:</label> <input type="text" name="newStreet"></p>
                <p><label>Hausnummer:</label> <input type="text" name="newHouseNumber"></p>
                <p><label>PLZ:</label> <input type="text" name="newPostalCode"></p>
                <p><label>Stadt:</label> <input type="text" name="newCity"></p>
                <p><label>Land/Region (ISO-3):</label> <input type="text" name="newCountry" value="DEU"></p>
                <p class="hint">
                    Aktuell werden diese Felder noch nicht über eine API ins ERP geschrieben.
                    Sobald eine <code>createCustomer</code>-Action implementiert ist, könnte hier
                    automatisch ein neuer ERP-Kunde angelegt und anschließend für die Bestellung verwendet werden.
                </p>
            </details>

            <p style="margin-top:20px;">
                <button type="submit" <?php echo empty($customers) ? 'disabled' : ''; ?>>
                    Bestellung abschließen
                </button>
            </p>
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

// Auswahl eines bestehenden Kunden (Dropdown)
$customerEmail  = trim($_POST['customerEmail'] ?? '');

// Felder aus der „alten Methode“ / Neukunde (aktuell nur konzeptionell)
$newCustomerName      = trim($_POST['newCustomerName']      ?? '');
$newCustomerEmail     = trim($_POST['newCustomerEmail']     ?? '');
$newStreet            = trim($_POST['newStreet']            ?? '');
$newHouseNumber       = trim($_POST['newHouseNumber']       ?? '');
$newPostalCode        = trim($_POST['newPostalCode']        ?? '');
$newCity              = trim($_POST['newCity']              ?? '');
$newCountry           = trim($_POST['newCountry']           ?? '');

// Validierung Produktdaten
if (!$productID || !$erpProductUUID || $price <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo "Ungültige Produktdaten.";
    exit;
}

// Aktuell ist die Bestellung an einen bestehenden ERP-Kunden gebunden.
// Daher ist die Auswahl im Dropdown Pflicht.
if (!$customerEmail) {
    http_response_code(400);
    echo "Bitte einen bestehenden Kunden aus der Liste auswählen.";
    exit;
}

try {
    // 1) Fresh stock check im ERP
    $product = $erp->getProductByProductId($productID);
    if (!$product) {
        throw new RuntimeException("Produkt im ERP nicht gefunden.");
    }
    if ((int)$product['stock'] < $quantity) {
        echo "Nicht genügend Bestand. Verfügbar: " . (int)$product['stock'];
        exit;
    }

    // 2) Kunde im ERP per E-Mail suchen (bestehender Kunde)
    $customer = $erp->findCustomerByEmail($customerEmail);

    if (!$customer) {
        // Hinweistext für den Use Case „Kunde anlegen“
        throw new RuntimeException(
            "Kein ERP-Kunde mit dieser E-Mail gefunden. " .
            "Über die einklappbare Sektion ist bereits vorgesehen, " .
            "zukünftig einen neuen Kunden direkt anzulegen (z.B. via createCustomer-API)."
        );
    }

    $customerId = $customer['customerID'];

    // HINWEIS:
    // Hier könnten zukünftig – falls eine createCustomer-API existiert –
    // die Felder $newCustomerName, $newCustomerEmail, $newStreet usw. genutzt werden, um:
    // 1. Einen neuen Kunden im ERP anzulegen.
    // 2. Die zurückgelieferte customerID dann als $customerId für die Order zu verwenden.

    // 3) Order-Position vorbereiten
    $itemAmount = $price * $quantity;
    $currency   = $product['currency'] ?? ($product['currency_code'] ?? 'EUR');

    $items = [
        [
            'product'    => $erpProductUUID, // UUID aus ERP
            'quantity'   => $quantity,
            'itemAmount' => $itemAmount,
            'currency'   => $currency,
        ],
    ];

    // 4) Order im ERP anlegen (RPC)
    $orderResponse = $erp->createOrder($customerId, $items, $currency);

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
            <p>Gesamtbetrag:
                <?php
                    echo number_format($itemAmount, 2, ',', '.')
                         . ' ' . htmlspecialchars($currency);
                ?>
            </p>
        </body>
        </html>
        <?php
        exit;
    }

    if ($status === 409) {
        // Konflikt – z.B. nicht genug Bestand
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
