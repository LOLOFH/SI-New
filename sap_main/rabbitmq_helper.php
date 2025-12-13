<?php
// rabbitmq_helper.php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendOrderMessageToQueue(array $order, string $queueName): bool {
    try {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Declare the queue to ensure it exists
        $channel->queue_declare($queueName, false, true, false, false);

        // Prepare Message Properties
        $properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type'  => 'application/json',
        ];

        // Only include correlation_id if set
        if (isset($order['correlationId'])) {
            $properties['correlation_id'] = $order['correlationId'];
        }

        // Remove reply_to — Karavan will ignore it anyway with disableReplyTo
        // if you want, you can keep it for reference in payload but not in AMQP headers
        unset($order['replyTo']);

        $msg = new AMQPMessage(
            json_encode($order, JSON_PRETTY_PRINT),
            $properties
        );

        $channel->basic_publish($msg, '', $queueName);

        $channel->close();
        $connection->close();
        return true;

    } catch (Throwable $e) {
        error_log("RabbitMQ error: " . $e->getMessage());
        return false;
    }
}
