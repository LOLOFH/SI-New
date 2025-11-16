<?php
// checkout.php

require_once __DIR__ . '/ErpClient.php';
require_once __DIR__ . '/../session.php';

// ERP client with Basic Auth
$erp = new ErpClient(
    'http://localhost:4004/rest/api', // adjust if necessary
    'service-user',
    'service-user'
);

// Wenn GET -> Formular anzeigen
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Product data
    $productID       = $_GET['productID']      ?? '';
    $erpProductUUID  = $_GET['erpProductUUID'] ?? '';
    $price           = $_GET['price']          ?? '';
    $quantity        = $_GET['quantity']       ?? 1;

    // Load customers from ERP
    try {
        $customers = $erp->getCustomers();
    } catch (Throwable $e) {
        http_response_code(500);
        echo "Error loading customers from ERP: " . htmlspecialchars($e->getMessage());
        exit;
    }
    
    // Include header for navigation
    include '../header.php';
    ?>
    <div class="card">
        <h2>🛒 Checkout</h2>
        <p>Review your order and choose a customer.</p>
    </div>

    <div class="card checkout-card">
        <form method="post">
            <div class="form-section">
                <h3>📦 Product details</h3>
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
                    <label>Price per unit:</label>
                    <input type="text" name="price"
                           value="<?php echo htmlspecialchars($price); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity"
                           value="<?php echo htmlspecialchars($quantity); ?>" min="1">
                </div>
            </div>

            <div class="form-section" style="margin-top:24px;">
                <h3>👤 Select Customer</h3>
                <?php if (empty($customers)): ?>
                    <p class="flash flash-err">No customers found in the ERP. Please create customers in the ERP first.</p>
                <?php else: ?>
                    <div class="form-group">
                        <label for="customerEmail">Customer (Email):</label>
                        <select name="customerEmail" id="customerEmail" required>
                            <option value="">-- please choose --</option>
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

            <!-- Collapsible section: create new customer -->
            <details class="details-section" style="margin-top:24px;padding:12px;border:1px solid #e5e7eb;border-radius:6px;">
                <summary style="cursor:pointer;font-weight:600;padding:6px 0;">➕ Create new customer (concept for future implementation)</summary>
                <div style="margin-top:12px;padding:12px;background:#f9fafb;border-radius:4px;font-size:14px;">
                    <p style="color:#666;margin:0 0 12px 0;">
                        This section shows the future concept of creating new customers directly in the webshop.
                        At the moment selecting an existing customer is required.
                    </p>
                    <div class="form-group">
                        <label for="newCustomerName">Name:</label>
                        <input type="text" id="newCustomerName" name="newCustomerName">
                    </div>
                    <div class="form-group">
                        <label for="newCustomerEmail">Email:</label>
                        <input type="email" id="newCustomerEmail" name="newCustomerEmail">
                    </div>
                    <div class="form-group">
                        <label for="newStreet">Street:</label>
                        <input type="text" id="newStreet" name="newStreet">
                    </div>
                    <div class="form-group">
                        <label for="newHouseNumber">House number:</label>
                        <input type="text" id="newHouseNumber" name="newHouseNumber">
                    </div>
                    <div class="form-group">
                        <label for="newPostalCode">Postal Code:</label>
                        <input type="text" id="newPostalCode" name="newPostalCode">
                    </div>
                    <div class="form-group">
                        <label for="newCity">City:</label>
                        <input type="text" id="newCity" name="newCity">
                    </div>
                    <div class="form-group">
                        <label for="newCountry">Country/Region (ISO-3):</label>
                        <input type="text" id="newCountry" name="newCountry" value="DEU">
                    </div>
                </div>
            </details>

            <div style="margin-top:24px;">
                <button type="submit" class="btn btn-primary" <?php echo empty($customers) ? 'disabled' : ''; ?>>
                    ✓ Place Order
                </button>
                <a href="/sap_main/products.php" class="btn btn-link" style="margin-left:12px;color:#6b7280;text-decoration:none;">← Back to products</a>
            </div>
        </form>
    </div>

    <?php
    include '../footer.php';
    exit;
}

// From here: POST -> submit order

$productID      = $_POST['productID']      ?? '';
$erpProductUUID = $_POST['erpProductUUID'] ?? '';
$price          = (float)($_POST['price']  ?? 0);
$quantity       = (int)($_POST['quantity'] ?? 1);

// Selection of an existing customer (dropdown)
$customerEmail  = trim($_POST['customerEmail'] ?? '');

// Fields from the "old method" / new customer (currently conceptual)
$newCustomerName      = trim($_POST['newCustomerName']      ?? '');
$newCustomerEmail     = trim($_POST['newCustomerEmail']     ?? '');
$newStreet            = trim($_POST['newStreet']            ?? '');
$newHouseNumber       = trim($_POST['newHouseNumber']       ?? '');
$newPostalCode        = trim($_POST['newPostalCode']        ?? '');
$newCity              = trim($_POST['newCity']              ?? '');
$newCountry           = trim($_POST['newCountry']           ?? '');

// Validate product data
if (!$productID || !$erpProductUUID || $price <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo "Invalid product data.";
    exit;
}

// Currently the order is bound to an existing ERP customer.
// Therefore the dropdown selection is mandatory.
if (!$customerEmail) {
    http_response_code(400);
    echo "Please select an existing customer from the list.";
    exit;
}

try {
    // 1) Fresh stock check in the ERP
    $product = $erp->getProductByProductId($productID);
    if (!$product) {
        throw new RuntimeException("Product not found in ERP.");
    }
    if ((int)$product['stock'] < $quantity) {
        echo "Not enough stock. Available: " . (int)$product['stock'];
        exit;
    }

    // 2) Find customer in ERP by email (existing customer)
    $customer = $erp->findCustomerByEmail($customerEmail);

    if (!$customer) {
        // Note for the use case "create customer"
        throw new RuntimeException(
            "No ERP customer found with this email. " .
            "The collapsible section already contains the concept to " .
            "create a new customer directly in the future (e.g. via createCustomer API)."
        );
    }

    $customerId = $customer['customerID'];

    // NOTE:
    // In the future – if a createCustomer API exists – the fields
    // $newCustomerName, $newCustomerEmail, $newStreet, etc. could be used to:
    // 1. Create a new customer in the ERP.
    // 2. Use the returned customerID as $customerId for the order.

    // 3) Prepare order item
    $itemAmount = $price * $quantity;
    $currency   = $product['currency'] ?? ($product['currency_code'] ?? 'EUR');

    $items = [
        [
            'product'    => $erpProductUUID, // UUID from ERP
            'quantity'   => $quantity,
            'itemAmount' => $itemAmount,
            'currency'   => $currency,
        ],
    ];

        // 4) Create order in ERP (RPC)
    $orderResponse = $erp->createOrder($customerId, $items, $currency);

    $status = $orderResponse['status'];
    $body   = $orderResponse['body'];

    if ($status >= 200 && $status < 300) {
        // Success – createOrder currently returns no body (typically 204)
        include '../header.php';
        ?>
        <div class="card success-card" style="background:#ecfdf5;border:2px solid #86efac;">
            <h2 style="color:#16a34a;margin-top:0;">✓ Thank you for your order!</h2>
            <p>The order has been successfully created in the ERP system.</p>
            
            <div class="order-summary" style="background:#f0fdf4;padding:16px;border-radius:6px;margin:16px 0;">
                <div class="summary-item" style="margin:8px 0;"><strong>📦 Product:</strong> <?php echo htmlspecialchars($product['name']); ?></div>
                <div class="summary-item" style="margin:8px 0;"><strong>📊 Quantity:</strong> <?php echo (int)$quantity; ?></div>
                <div class="summary-item" style="margin:8px 0;"><strong>💰 Total:</strong>
                    <?php
                        echo number_format($itemAmount, 2, ',', '.')
                             . ' ' . htmlspecialchars($currency);
                    ?>
                </div>
            </div>
            
            <div style="margin-top:24px;">
                <a href="/sap_main/products.php" class="btn btn-primary">← Back to products</a>
            </div>
        </div>
        <?php
        include '../footer.php';
        exit;
    }

    if ($status === 409) {
        // Conflict – e.g. not enough stock
        include '../header.php';
        ?>
        <div class="card error-card" style="background:#fef2f2;border:2px solid #fecaca;">
            <h2 style="color:#991b1b;margin-top:0;">⚠ Order could not be created</h2>
            <p>Not enough stock in the ERP.</p>
            <?php if ($body): ?>
                <pre style="background:#fff;padding:12px;border-radius:4px;overflow:auto;border:1px solid #fecaca;"><?php echo htmlspecialchars($body); ?></pre>
            <?php endif; ?>
            <a href="/sap_main/products.php" class="btn btn-primary">← Back to products</a>
        </div>
        <?php
        include '../footer.php';
        exit;
    }

    // Other errors
    include '../header.php';
    ?>
    <div class="card error-card" style="background:#fef2f2;border:2px solid #fecaca;">
        <h2 style="color:#991b1b;margin-top:0;">⚠ Error creating the order</h2>
        <p>HTTP status: <?php echo (int)$status; ?></p>
        <?php if ($body): ?>
            <pre style="background:#fff;padding:12px;border-radius:4px;overflow:auto;border:1px solid #fecaca;"><?php echo htmlspecialchars($body); ?></pre>
        <?php endif; ?>
        <a href="/sap_main/products.php" class="btn btn-primary">← Back to products</a>
    </div>
    <?php
    include '../footer.php';

} catch (Throwable $e) {
    http_response_code(500);
    include '../header.php';
    ?>
    <div class="card error-card" style="background:#fef2f2;border:2px solid #fecaca;">
        <h2 style="color:#991b1b;margin-top:0;">⚠ Checkout error</h2>
        <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
        <a href="/sap_main/products.php" class="btn btn-primary">← Back to products</a>
    </div>
    <?php
    include '../footer.php';
}
