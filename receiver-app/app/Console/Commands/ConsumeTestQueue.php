<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Handlers\NotificationHandler;
use App\Services\ConsumedEventsService;

class ConsumeTestQueue extends Command
{
    protected $signature = 'rabbitmq:test';

    protected $description = 'Consume messages from test queue';

    public function handle(): int
    {
        $consumedEventsService = app(ConsumedEventsService::class);
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
            passive: true,
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
        $this->info('Waiting for messages...');

        $callback = function ($message) {
            $consumedEventsService = app(ConsumedEventsService::class);
        

            try {
                
                $data = json_decode(
                    $message->body,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
                if (!$consumedEventsService->insertOrIgnore($data['event_uuid'])) {
                    $message->ack();
                    return;
                }

                app(NotificationHandler::class)->handle($data);

                $message->ack();

            } catch (\Throwable $e) {

                $this->error($e->getMessage());

                $message->nack(false, false);
            }
        };

        $channel->basic_consume(
            'test',
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return self::SUCCESS;
    }
}