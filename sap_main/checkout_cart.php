<?php
// checkout_cart.php - Checkout from the cart

require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cart_handler.php';
require_once __DIR__ . '/ErpClient.php';
require_once __DIR__ . '/rabbitmq_helper.php';  // NEW for Lab 6

// Initialize ERP client (still needed to fetch customers)
$erp = new ErpClient(
    'http://localhost:4004/rest/api',
    'service-user',
    'service-user'
);

$cart = get_cart();
$cartTotal = get_cart_total();

// If the cart is empty, redirect
if (empty($cart)) {
    header('Location: /cart.php');
    exit;
}

// Fetch customers from ERP
$customers = [];
try {
    $customers = $erp->getCustomers();
} catch (Throwable $e) {
    // Will show error message later
}

// POST - process order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerEmail = trim($_POST['customerEmail'] ?? '');

    if (!$customerEmail) {
        $error = 'Please select a customer.';
    } else {
        try {
            // Find customer by Email
            $customer = $erp->findCustomerByEmail($customerEmail);
            if (!$customer) {
                throw new RuntimeException("Customer not found.");
            }

            $customerId = $customer['customerID'];

            // Prepare order items
            $items = [];
            foreach ($cart as $cartItem) {
                $items[] = [
                    'product' => $cartItem['erpProductUUID'],
                    'quantity' => (int)$cartItem['quantity'],
                    'itemAmount' => (float)($cartItem['price'] * $cartItem['quantity']),
                    'currency' => $cartItem['currency']
                ];
            }

            /// Final message for RabbitMQ (only order, no correlationId)
//$orderMessage = [
    //'customerEmail' => $customerEmail,
    //'orderDate'     => date('Y-m-d'),
    ///'currency'      => 'EUR',
    //'orderAmount'   => $cartTotal,
    //'items'         => $items,
//];
 $orderAmount = 0.0;
foreach ($items as $item) {
    $orderAmount += (float)$item['itemAmount'];
}

// Build payload exactly as expected by simpleerp-api.js
// Build payload exactly as expected by Karavan (Lab 6)
$correlationId = uniqid('order_', true); 

$payload = [
    'correlationId' => $correlationId, // Critical for tracking
    'order' => [
        'customer'    => $customerId,
        'orderDate'   => date('Y-m-d'),
        'orderAmount' => $cartTotal,
        'currency'    => 'EUR',
        'items'       => []
    ]
];

$itemId = 1;
foreach ($cart as $cartItem) {
    $payload['order']['items'][] = [
        'itemID'     => $itemId++,                  // simpleerp-api.js expects 'itemID'
        'product'    => $cartItem['erpProductUUID'],
        'quantity'   => (int)$cartItem['quantity'],
        'itemAmount' => (float)($cartItem['price'] * $cartItem['quantity']),
        'currency'   => $cartItem['currency'] ?? 'EUR',
    ];
}

// Send to RabbitMQ
$sent = sendOrderMessageToQueue($payload, 'webshop-orders-in');



            if ($sent) {
                clear_cart();
                $_SESSION['orderSuccess'] = true;
                $_SESSION['orderTotal'] = $cartTotal;
                $_SESSION['orderItemsCount'] = count($cart);

                header('Location: /checkout_success.php');
                exit;
            } else {
                $error = "Order could not be sent to RabbitMQ.";
            }

        } catch (Throwable $e) {
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

include '../header.php';
?>


<div class="card">
    <h2>💳 Complete Order</h2>
    <p>Review your order and select a customer.</p>
</div>

<?php if (isset($error)): ?>
    <div class="card flash flash-err">
        <strong>⚠ Error:</strong> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Cart overview -->
<div class="card">
    <h3>📦 Your Items</h3>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
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
                <td colspan="3" style="text-align:right;">Total:</td>
                <td><?php echo number_format($cartTotal, 2, ',', '.') . ' EUR'; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Customer selection -->
<div class="card" style="max-width:600px;">
    <h3>👤 Select Customer</h3>
    
    <?php if (empty($customers)): ?>
        <p class="flash flash-err">No customers found in the ERP. Please create customers in the ERP first.</p>
    <?php else: ?>
        <form method="post">
            <div class="form-group">
                <label for="customerEmail">Customer (Email):</label>
                <select name="customerEmail" id="customerEmail" required>
                    <option value="">-- Please choose --</option>
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
                    ← Back to cart
                </a>
                <button type="submit" class="btn btn-primary" style="flex:1;">
                    ✓ Place Order
                </button>
            </div>

            <!-- Collapsible section: create new customer (conceptual) -->
            <details class="details-section" style="margin-top:24px;padding:12px;border:1px solid #e5e7eb;border-radius:6px;">
                <summary style="cursor:pointer;font-weight:600;padding:6px 0;">➕ Create new customer (concept for future implementation)</summary>
                <div style="margin-top:12px;padding:12px;background:#f9fafb;border-radius:4px;font-size:14px;">
                    <p style="color:#666;margin:0 0 12px 0;">
                        This section shows the future concept for creating new customers directly in the webshop.
                        At the moment selecting an existing customer is required.
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
                    <p style="color:#666;margin:12px 0 0 0;font-size:13px;">
                        These fields are not currently written to the ERP via an API.
                        Once a <code>createCustomer</code> action is implemented, a new ERP customer
                        could be created here and used for the order.
                    </p>
                </div>
            </details>
        </form>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
