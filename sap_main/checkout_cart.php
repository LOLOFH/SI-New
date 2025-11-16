<?php
// checkout_cart.php - Checkout aus dem Warenkorb

require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cart_handler.php';
require_once __DIR__ . '/ErpClient.php';

// ERP-Client initialisieren
$erp = new ErpClient(
    'http://localhost:4004/rest/api',
    'service-user',
    'service-user'
);

$cart = get_cart();
$cartTotal = get_cart_total();

// Wenn Warenkorb leer ist, redirect
if (empty($cart)) {
    header('Location: /cart.php');
    exit;
}

// Hole Kunden aus ERP
$customers = [];
try {
    $customers = $erp->getCustomers();
} catch (Throwable $e) {
    // Fehler wird später angezeigt
}

// POST - Bestellung verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerEmail = trim($_POST['customerEmail'] ?? '');
    
    if (!$customerEmail) {
        $error = 'Bitte einen Kunden auswählen.';
    } else {
        try {
            // Kunde suchen
            $customer = $erp->findCustomerByEmail($customerEmail);
            if (!$customer) {
                throw new RuntimeException("Kunde nicht gefunden.");
            }
            
            $customerId = $customer['customerID'];
            
            // Bestellpositionen vorbereiten
            $items = [];
            foreach ($cart as $cartItem) {
                $items[] = [
                    'product' => $cartItem['erpProductUUID'],
                    'quantity' => (int)$cartItem['quantity'],
                    'itemAmount' => (float)($cartItem['price'] * $cartItem['quantity']),
                    'currency' => $cartItem['currency']
                ];
            }
            
            // Bestellung im ERP erstellen
            $orderResponse = $erp->createOrder($customerId, $items, 'EUR');
            $status = $orderResponse['status'];
            
            if ($status >= 200 && $status < 300) {
                // Erfolg - Warenkorb leeren und weiterleiten
                clear_cart();
                $_SESSION['orderSuccess'] = true;
                $_SESSION['orderTotal'] = $cartTotal;
                $_SESSION['orderItemsCount'] = count($cart);
                
                header('Location: /checkout_success.php');
                exit;
            } else {
                $error = 'Fehler beim Erstellen der Bestellung (HTTP ' . $status . ')';
            }
        } catch (Throwable $e) {
            $error = 'Fehler: ' . htmlspecialchars($e->getMessage());
        }
    }
}

include '../header.php';
?>

<div class="card">
    <h2>💳 Bestellung abschließen</h2>
    <p>Überprüfen Sie Ihre Bestellung und geben Sie einen Kunden an.</p>
</div>

<?php if (isset($error)): ?>
    <div class="card flash flash-err">
        <strong>⚠ Fehler:</strong> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Warenkorb-Übersicht -->
<div class="card">
    <h3>📦 Ihre Artikel</h3>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Preis</th>
                <th>Menge</th>
                <th>Gesamtbetrag</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart as $item): ?>
                <?php $itemTotal = $item['price'] * $item['quantity']; ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo number_format($item['price'], 2, ',', '.') . ' ' . htmlspecialchars($item['currency']); ?></td>
                    <td><?php echo (int)$item['quantity']; ?></td>
                    <td><?php echo number_format($itemTotal, 2, ',', '.') . ' ' . htmlspecialchars($item['currency']); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="background:#f3f4f6;font-weight:700;">
                <td colspan="3" style="text-align:right;">Gesamtbetrag:</td>
                <td><?php echo number_format($cartTotal, 2, ',', '.') . ' EUR'; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Kundenauswahl -->
<div class="card" style="max-width:600px;">
    <h3>👤 Kunde auswählen</h3>
    
    <?php if (empty($customers)): ?>
        <p class="flash flash-err">Es sind keine Kunden im ERP vorhanden. Bitte zuerst Kunden im ERP anlegen.</p>
    <?php else: ?>
        <form method="post">
            <div class="form-group">
                <label for="customerEmail">Kunde (E-Mail):</label>
                <select name="customerEmail" id="customerEmail" required>
                    <option value="">-- Bitte wählen --</option>
                    <?php foreach ($customers as $c): ?>
                        <?php
                            $email = $c['email'] ?? '';
                            $name = $c['name'] ?? '';
                            if (!$email) continue;
                        ?>
                        <option value="<?php echo htmlspecialchars($email); ?>">
                            <?php echo htmlspecialchars($email . ($name ? ' - ' . $name : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display:flex;gap:12px;margin-top:24px;">
                <a href="/cart.php" class="btn btn-link" style="text-decoration:none;">
                    ← Zurück zum Warenkorb
                </a>
                <button type="submit" class="btn btn-primary" style="flex:1;">
                    ✓ Bestellung abschließen
                </button>
            </div>

            <!-- Einklappbarer Bereich: neuer Kunde anlegen (konzeptionell) -->
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
                    <p style="color:#666;margin:12px 0 0 0;font-size:13px;">
                        Aktuell werden diese Felder noch nicht über eine API ins ERP geschrieben.
                        Sobald eine <code>createCustomer</code>-Action implementiert ist, könnte hier
                        automatisch ein neuer ERP-Kunde angelegt und anschließend für die Bestellung verwendet werden.
                    </p>
                </div>
            </details>
        </form>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
