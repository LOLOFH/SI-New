<?php
// sendOrderMessage.php
// Sends order data to RabbitMQ for Lab 6 (Karavan routing)

// Make sure composer autoload is included if using php-amqplib
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Send order to RabbitMQ
 *
 * @param array $order Associative array with order details
 * @throws Exception if message cannot be sent
 */
function sendOrderMessage(array $order): void
{
    // RabbitMQ configuration
    $host     = 'localhost';    // adjust as needed
    $port     = 5672;
    $user     = 'guest';
    $password = 'guest';
    $vhost    = '/';

    // Queue names
    $requestQueue  = 'webshop-orders-in';
    $responseQueue = 'webshop-orders-out'; // optional for correlation


    try {
        // 1) Connect to RabbitMQ
        $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $channel = $connection->channel();

        // 2) Declare queues (durable)
        $channel->queue_declare($requestQueue, false, false, false, false);
        $channel->queue_declare($responseQueue, false, false, false, false);

        // 3) Convert order to JSON
        $messageBody = json_encode($order, JSON_UNESCAPED_UNICODE);
        if ($messageBody === false) {
            throw new RuntimeException('Failed to encode order to JSON.');
        }

        // 4) Create message with persistent delivery mode
        $msg = new AMQPMessage(
            $messageBody,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        // 5) Publish message to the request queue
        $channel->basic_publish($msg, '', $requestQueue);

        // Optional: log message sent
        error_log("Order sent to RabbitMQ: " . $order['order_id']);

        // 6) Close channel and connection
        $channel->close();
        $connection->close();

    } catch (Throwable $e) {
        // Log error and rethrow
        error_log("Failed to send order to RabbitMQ: " . $e->getMessage());
        throw new RuntimeException("Failed to send order message: " . $e->getMessage());
    }
}

function receiveERPResponse() {
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();
    $channel->queue_declare('webshop-orders-out', false, false, false, false);

    $callback = function($msg) {
        $response = json_decode($msg->body, true);
        // Update order status in webshop DB
        // updateOrderStatus($response['order_id'], $response['status']);
    };

    $channel->basic_consume('webshop-orders-out', '', false, false, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
}

