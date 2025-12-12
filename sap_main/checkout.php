<?php
// checkout.php

require_once __DIR__ . '/ErpClient.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/sendOrderMessage.php'; // fixed path

// ERP client with Basic Auth (still optional, can be used for stock check)
$erp = new ErpClient(
    'http://localhost:4004/rest/api', // adjust if necessary
    'service-user',
    'service-user'
);

// When GET -> display form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $productID       = $_GET['productID']      ?? '';
    $erpProductUUID  = $_GET['erpProductUUID'] ?? '';
    $price           = $_GET['price']          ?? '';
    $quantity        = $_GET['quantity']       ?? 1;

    try {
        $customers = $erp->getCustomers();
    } catch (Throwable $e) {
        http_response_code(500);
        echo "Error loading customers from ERP: " . htmlspecialchars($e->getMessage());
        exit;
    }

    include '../header.php';
    ?>
    <div class="card">
        <h2>🛒 Checkout</h2>
        <p>Review your order and choose a customer.</p>
    </div>

    <div class="card checkout-card">
        <form method="post">
            <!-- Product details -->
            <div class="form-section">
                <h3>📦 Product details</h3>
                <div class="form-group">
                    <label>Product ID:</label>
                    <input type="text" name="productID" value="<?php echo htmlspecialchars($productID); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>ERP Product UUID:</label>
                    <input type="text" name="erpProductUUID" value="<?php echo htmlspecialchars($erpProductUUID); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Price per unit:</label>
                    <input type="text" name="price" value="<?php echo htmlspecialchars($price); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" min="1">
                </div>
            </div>

            <!-- Select existing customer -->
            <div class="form-section" style="margin-top:24px;">
                <h3>👤 Select Customer</h3>
                <?php if (empty($customers)): ?>
                    <p class="flash flash-err">No customers found in the ERP. Please create customers first.</p>
                <?php else: ?>
                    <div class="form-group">
                        <label for="customerEmail">Customer (Email):</label>
                        <select name="customerEmail" id="customerEmail" required>
                            <option value="">-- please choose --</option>
                            <?php foreach ($customers as $c): ?>
                                <?php $email = $c['email'] ?? ''; $name = $c['name'] ?? ''; if (!$email) continue; ?>
                                <option value="<?php echo htmlspecialchars($email); ?>">
                                    <?php echo htmlspecialchars($email . ($name ? ' - ' . $name : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Collapsible section: future create new customer -->
            <details class="details-section" style="margin-top:24px;padding:12px;border:1px solid #e5e7eb;border-radius:6px;">
                <summary style="cursor:pointer;font-weight:600;padding:6px 0;">➕ Create new customer (future)</summary>
                <div style="margin-top:12px;padding:12px;background:#f9fafb;border-radius:4px;font-size:14px;">
                    <p style="color:#666;margin:0 0 12px 0;">
                        Currently, selecting an existing customer is mandatory.
                    </p>
                    <!-- Form fields omitted for brevity -->
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

// POST -> submit order
$productID      = $_POST['productID'] ?? '';
$erpProductUUID = $_POST['erpProductUUID'] ?? '';
$price          = (float)($_POST['price'] ?? 0);
$quantity       = (int)($_POST['quantity'] ?? 1);
$customerEmail  = trim($_POST['customerEmail'] ?? '');

if (!$productID || !$erpProductUUID || $price <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo "Invalid product data.";
    exit;
}

if (!$customerEmail) {
    http_response_code(400);
    echo "Please select an existing customer.";
    exit;
}

try {
    // Optional: check stock via ERP
    $product = $erp->getProductByProductId($productID);
    if (!$product) throw new RuntimeException("Product not found in ERP.");
    if ((int)$product['stock'] < $quantity) {
        echo "Not enough stock. Available: " . (int)$product['stock'];
        exit;
    }

    $customer = $erp->findCustomerByEmail($customerEmail);
    if (!$customer) throw new RuntimeException("No ERP customer found with this email.");
    $customerId = $customer['customerID'];

    // Prepare order payload for RabbitMQ
    $order = [
        'order_id'       => uniqid('order_', true),
        'customer_id'    => $customerId,
        'customer_email' => $customerEmail,
        'products' => [
            [
                'product_id' => $productID,
                'erp_product_uuid' => $erpProductUUID,
                'quantity' => $quantity,
                'price' => $price,
                'currency' => $product['currency'] ?? 'EUR',
            ]
        ],
        'total_amount' => $price * $quantity,
        'order_date' => date('c'),
        'correlation_id' => uniqid('corr_', true),
    ];

    // Send order to RabbitMQ
    require_once __DIR__ . '/rabbitmq_helper.php';

// Prepare order payload
$orderPayload = [
    'customerEmail' => $customerEmail,
    'orderDate' => date('Y-m-d'),
    'currency' => 'EUR',
    'items' => []
];

foreach ($cart as $cartItem) {
    $orderPayload['items'][] = [
        'product' => $cartItem['erpProductUUID'],
        'quantity' => (int)$cartItem['quantity'],
        'itemAmount' => (float)($cartItem['price'] * $cartItem['quantity']),
        'currency' => $cartItem['currency']
    ];
}

// Send to RabbitMQ
sendOrderMessage($orderPayload, 'webshop-orders-in');

// Assume success and clear cart (response will come asynchronously)
clear_cart();
$_SESSION['orderSuccess'] = true;
$_SESSION['orderTotal'] = $cartTotal;
$_SESSION['orderItemsCount'] = count($cart);

header('Location: /checkout_success.php');
exit;


    include '../header.php';
    ?>
    <div class="card success-card" style="background:#ecfdf5;border:2px solid #86efac;">
        <h2 style="color:#16a34a;margin-top:0;">✓ Thank you for your order!</h2>
        <p>Your order has been successfully sent to the ERP system for processing.</p>
        <div class="order-summary" style="background:#f0fdf4;padding:16px;border-radius:6px;margin:16px 0;">
            <div class="summary-item" style="margin:8px 0;"><strong>📦 Product:</strong> <?php echo htmlspecialchars($product['name']); ?></div>
            <div class="summary-item" style="margin:8px 0;"><strong>📊 Quantity:</strong> <?php echo (int)$quantity; ?></div>
            <div class="summary-item" style="margin:8px 0;"><strong>💰 Total:</strong> <?php echo number_format($price*$quantity,2,',','.') . ' ' . htmlspecialchars($product['currency'] ?? 'EUR'); ?></div>
        </div>
        <div style="margin-top:24px;">
            <a href="/sap_main/products.php" class="btn btn-primary">← Back to products</a>
        </div>
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
