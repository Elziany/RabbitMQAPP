<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher
{
    public function publish(array $message)
    {
        $config = config('queue.connections.rabbitmq');

        $host = $config['hosts'][0];

        $connection = new AMQPStreamConnection(
            $host['host'],
            $host['port'],
            $host['user'],
            $host['password'],
            $host['vhost']
        );

        $channel = $connection->channel();

        $channel->exchange_declare(
            'test_dlx',
            'direct',
            false,
            true,
            false
        );

        $channel->queue_declare(
            'test_dlq',
            false,
            true,
            false,
            false
        );

        $channel->queue_bind(
            'test_dlq',
            'test_dlx',
            'test_failed'
        );

        $channel->exchange_declare(
            'notifications',
            'direct',
            false,
            true,
            false,
            false
        );

        $channel->queue_declare(
            queue: 'test',
            durable: true,
            auto_delete: false,
            arguments: [
                'x-dead-letter-exchange' => ['S', 'test_dlx'],
                'x-dead-letter-routing-key' => ['S', 'test_failed'],
            ]
        );

        $channel->queue_bind(
            'test',
            'notifications',
            'test'
        );

        $msg = new AMQPMessage(
            json_encode($message),
            [
                'content_type' => 'application/json',
                'delivery_mode' => 2,
            ]
        );
        $channel->basic_publish(
        $msg,
            'notifications',
            'test'
        );
        $channel->close();
        $connection->close();
    }
}