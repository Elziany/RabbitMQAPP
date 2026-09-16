<?php

namespace App\Services;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    const EXCHANGE_DEFAULT_TYPE = 'direct';
    const PRESISTANCE_DELIVERY_MODE = 2;

    public function publish(array $message)
    {

        $connection = $this->makeConnection();

        $channel = $connection->channel();
        $this->createQueue(
            channel: $channel,
            queueName: 'test_dlq',
            exchangeName: 'test_dlx',
            routingKey: 'test_failed'
        );
        $this->createQueue(
            channel: $channel,
            queueName: 'test',
            exchangeName: 'notifications',
            routingKey: 'test',
            arguments: [
                'x-dead-letter-exchange' => ['S', 'test_dlx'],
                'x-dead-letter-routing-key' => ['S', 'test_failed'],
            ]
        );
        $msg = new AMQPMessage(
            json_encode($message),
            [
                'content_type' => 'application/json',
                'delivery_mode' => self::PRESISTANCE_DELIVERY_MODE,
            ]
        );
        $channel->basic_publish(
        $msg,
            exchange: 'notifications',
            routing_key: 'test'
        );
        $channel->close();
        $connection->close();
    }

    public function makeConnection(): AMQPStreamConnection
    {
        $config = config('queue.connections.rabbitmq');

        $host = $config['hosts'][0];

        return new AMQPStreamConnection(
            $host['host'],
            $host['port'],
            $host['user'],
            $host['password'],
            $host['vhost']
        );
    }

    public function createQueue(AMQPChannel $channel , string $queueName, string $exchangeName, string $routingKey ,array $arguments = []): void
    {
     
        $channel->exchange_declare(
            exchange: $exchangeName,
            type: self::EXCHANGE_DEFAULT_TYPE,
            passive: false,
            durable: true,
            auto_delete: false
        );

        $channel->queue_declare(
            queue: $queueName,
            passive: false, 
            durable: true,
            auto_delete: false,
            arguments: $arguments
        );

        $channel->queue_bind(
            queue: $queueName,
            exchange: $exchangeName,
            routing_key: $routingKey
        );

    }
}