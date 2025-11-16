<?php
// checkout.php

require_once __DIR__ . '/ErpClient.php';
require_once __DIR__ . '/../session.php';

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
    
    // Include header für Navigation
    include '../header.php';
    ?>
    <div class="card">
        <h2>🛒 Checkout</h2>
        <p>Überprüfen Sie Ihre Bestellung und wählen Sie einen Kunden.</p>
    </div>

    <div class="card checkout-card">
        <form method="post">
            <div class="form-section">
                <h3>📦 Produktdetails</h3>
                <div class="form-group">
                    <label>Product ID:</label>
                    <input type="text" name="productID"
                           value="<?php echo htmlspecialchars($productID); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>ERP Product UUID:</label>
                    <input type="text" name="erpProductUUID"
                           value="<?php echo htmlspecialchars($erpProductUUID); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Preis pro Stück:</label>
                    <input type="text" name="price"
                           value="<?php echo htmlspecialchars($price); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="quantity">Menge:</label>
                    <input type="number" id="quantity" name="quantity"
                           value="<?php echo htmlspecialchars($quantity); ?>" min="1">
                </div>
            </div>

            <div class="form-section" style="margin-top:24px;">
                <h3>👤 Kunde auswählen</h3>
                <?php if (empty($customers)): ?>
                    <p class="flash flash-err">Es sind keine Kunden im ERP vorhanden. Bitte zuerst Kunden im ERP anlegen.</p>
                <?php else: ?>
                    <div class="form-group">
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
                    </div>
                <?php endif; ?>
            </div>

            <!-- Einklappbarer Bereich: Neue Kunde anlegen -->
            <details class="details-section" style="margin-top:24px;padding:12px;border:1px solid #e5e7eb;border-radius:6px;">
                <summary style="cursor:pointer;font-weight:600;padding:6px 0;">➕ Neuen Kunden anlegen (Konzept für zukünftige Implementierung)</summary>
                <div style="margin-top:12px;padding:12px;background:#f9fafb;border-radius:4px;font-size:14px;">
                    <p style="color:#666;margin:0 0 12px 0;">
                        Diese Sektion zeigt das zukünftige Konzept zum Anlegen neuer Kunden direkt im Webshop.
                        Aktuell ist die Auswahl eines bestehenden Kunden erforderlich.
                    </p>
                    <div class="form-group">
                        <label for="newCustomerName">Name:</label>
                        <input type="text" id="newCustomerName" name="newCustomerName">
                    </div>
                    <div class="form-group">
                        <label for="newCustomerEmail">E-Mail:</label>
                        <input type="email" id="newCustomerEmail" name="newCustomerEmail">
                    </div>
                    <div class="form-group">
                        <label for="newStreet">Straße:</label>
                        <input type="text" id="newStreet" name="newStreet">
                    </div>
                    <div class="form-group">
                        <label for="newHouseNumber">Hausnummer:</label>
                        <input type="text" id="newHouseNumber" name="newHouseNumber">
                    </div>
                    <div class="form-group">
                        <label for="newPostalCode">PLZ:</label>
                        <input type="text" id="newPostalCode" name="newPostalCode">
                    </div>
                    <div class="form-group">
                        <label for="newCity">Stadt:</label>
                        <input type="text" id="newCity" name="newCity">
                    </div>
                    <div class="form-group">
                        <label for="newCountry">Land/Region (ISO-3):</label>
                        <input type="text" id="newCountry" name="newCountry" value="DEU">
                    </div>
                </div>
            </details>

            <div style="margin-top:24px;">
                <button type="submit" class="btn btn-primary" <?php echo empty($customers) ? 'disabled' : ''; ?>>
                    ✓ Bestellung abschließen
                </button>
                <a href="/sap_main/products.php" class="btn btn-link" style="margin-left:12px;color:#6b7280;text-decoration:none;">← Zurück zu Produkten</a>
            </div>
        </form>
    </div>

    <?php
    include '../footer.php';
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
        include '../header.php';
        ?>
        <div class="card success-card" style="background:#ecfdf5;border:2px solid #86efac;">
            <h2 style="color:#16a34a;margin-top:0;">✓ Vielen Dank für Ihre Bestellung!</h2>
            <p>Die Bestellung wurde erfolgreich im ERP-System angelegt.</p>
            
            <div class="order-summary" style="background:#f0fdf4;padding:16px;border-radius:6px;margin:16px 0;">
                <div class="summary-item" style="margin:8px 0;"><strong>📦 Produkt:</strong> <?php echo htmlspecialchars($product['name']); ?></div>
                <div class="summary-item" style="margin:8px 0;"><strong>📊 Menge:</strong> <?php echo (int)$quantity; ?></div>
                <div class="summary-item" style="margin:8px 0;"><strong>💰 Gesamtbetrag:</strong>
                    <?php
                        echo number_format($itemAmount, 2, ',', '.')
                             . ' ' . htmlspecialchars($currency);
                    ?>
                </div>
            </div>
            
            <div style="margin-top:24px;">
                <a href="/sap_main/products.php" class="btn btn-primary">← Zurück zu Produkten</a>
            </div>
        </div>
        <?php
        include '../footer.php';
        exit;
    }

    if ($status === 409) {
        // Konflikt – z.B. nicht genug Bestand
        include '../header.php';
        ?>
        <div class="card error-card" style="background:#fef2f2;border:2px solid #fecaca;">
            <h2 style="color:#991b1b;margin-top:0;">⚠ Bestellung konnte nicht angelegt werden</h2>
            <p>Nicht genügend Bestand im ERP.</p>
            <?php if ($body): ?>
                <pre style="background:#fff;padding:12px;border-radius:4px;overflow:auto;border:1px solid #fecaca;"><?php echo htmlspecialchars($body); ?></pre>
            <?php endif; ?>
            <a href="/sap_main/products.php" class="btn btn-primary">← Zurück zu Produkten</a>
        </div>
        <?php
        include '../footer.php';
        exit;
    }

    // Andere Fehler
    include '../header.php';
    ?>
    <div class="card error-card" style="background:#fef2f2;border:2px solid #fecaca;">
        <h2 style="color:#991b1b;margin-top:0;">⚠ Fehler beim Anlegen der Bestellung</h2>
        <p>HTTP-Status: <?php echo (int)$status; ?></p>
        <?php if ($body): ?>
            <pre style="background:#fff;padding:12px;border-radius:4px;overflow:auto;border:1px solid #fecaca;"><?php echo htmlspecialchars($body); ?></pre>
        <?php endif; ?>
        <a href="/sap_main/products.php" class="btn btn-primary">← Zurück zu Produkten</a>
    </div>
    <?php
    include '../footer.php';

} catch (Throwable $e) {
    http_response_code(500);
    include '../header.php';
    ?>
    <div class="card error-card" style="background:#fef2f2;border:2px solid #fecaca;">
        <h2 style="color:#991b1b;margin-top:0;">⚠ Fehler im Checkout</h2>
        <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
        <a href="/sap_main/products.php" class="btn btn-primary">← Zurück zu Produkten</a>
    </div>
    <?php
    include '../footer.php';
}
