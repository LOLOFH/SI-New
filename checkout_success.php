<?php
// checkout_success.php - Confirmation page after successful order

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/cart_handler.php';
require_once __DIR__ . '/vendor/autoload.php'; // for php-amqplib if you use composer

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Check if the order was successful
if (empty($_SESSION['orderSuccess'])) {
    header('Location: /index.php');
    exit;
}

$orderTotal = $_SESSION['orderTotal'] ?? 0;
$orderItemsCount = $_SESSION['orderItemsCount'] ?? 0;

// Cleanup session
unset($_SESSION['orderSuccess']);
unset($_SESSION['orderTotal']);
unset($_SESSION['orderItemsCount']);

// --- RabbitMQ Setup to get ERP response ---
$erpResponse = null;
try {
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    $responseQueue = 'webshop-orders-out';
    $channel->queue_declare($responseQueue, false, false, false, false);

    // Consume message (non-blocking)
    $callback = function($msg) use (&$erpResponse) {
        $erpResponse = json_decode($msg->body, true);
    };

    $channel->basic_consume($responseQueue, '', false, false, false, false, $callback);

    // Wait a short time (max 2 seconds) for a message
    $start = microtime(true);
    while ($channel->is_consuming() && (microtime(true) - $start) < 2) {
        $channel->wait(null, true);
    }

    $channel->close();
    $connection->close();
} catch (Throwable $e) {
    // Log or ignore errors
    $erpResponse = ['error' => $e->getMessage()];
}

include 'header.php';
?>

<div class="card success-card" style="text-align:center;background:#ecfdf5;border:2px solid #86efac;padding:40px;">
    <h2 style="color:#16a34a;margin-top:0;font-size:32px;">✓ Order successful!</h2>
    <p style="font-size:18px;color:#666;margin:20px 0;">
        Thank you for your purchase.
    </p>
    
    <div style="background:#f0fdf4;padding:24px;border-radius:8px;margin:24px 0;max-width:500px;margin-left:auto;margin-right:auto;">
        <div style="margin:16px 0;">
            <p style="color:#666;margin:0 0 8px 0;">Number of items:</p>
            <p style="font-size:24px;font-weight:700;color:#16a34a;margin:0;"><?php echo $orderItemsCount; ?></p>
        </div>
        
        <div style="margin:16px 0;padding-top:16px;border-top:2px solid #d1fae5;">
            <p style="color:#666;margin:0 0 8px 0;">Total:</p>
            <p style="font-size:28px;font-weight:700;color:#2563eb;margin:0;">
                <?php echo number_format($orderTotal, 2, ',', '.'); ?> EUR
            </p>
        </div>

        <?php if ($erpResponse): ?>
            <div style="margin-top:24px;padding:16px;background:#e0f2fe;border-radius:6px;">
                <strong>ERP Response:</strong><br>
                <pre style="text-align:left;"><?php echo htmlspecialchars(print_r($erpResponse, true)); ?></pre>
            </div>
        <?php endif; ?>
    </div>
    
    <p style="font-size:14px;color:#666;margin:20px 0;">
        An order confirmation will be sent to your email address.
    </p>
    
    <div style="display:flex;gap:12px;justify-content:center;margin-top:32px;">
        <a href="/index.php" class="btn btn-link" style="text-decoration:none;">
            Go to homepage
        </a>
        <a href="/sap_main/products.php" class="btn btn-primary" style="text-decoration:none;">
            Continue shopping
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>
