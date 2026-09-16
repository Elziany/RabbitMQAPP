<?php

namespace App\Console\Commands;

use App\Services\OutboxMessageService;
use App\Services\RabbitMQService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:outbox')]
#[Description('Command description')]
class Outbox extends Command
{

     public function handle(
        OutboxMessageService $outboxMessageService,
        RabbitMQService $rabbitMQService
    ): int {

        $this->info('Outbox publisher started...');

        while (true) {

            $messages = $outboxMessageService->getPending(100);

            foreach ($messages as $message) {

                try {

                    $rabbitMQService->publish([
                        'event_type' => $message->event_type,
                        'payload' => json_decode($message->payload, true),
                        'event_uuid' => $message->event_uuid,
                    ]);

                    $outboxMessageService->markAsDispatched($message->id);

                } catch (\Throwable $e) {

                    $this->error($e->getMessage());
                }
            }

            sleep(1);
        }

        return self::SUCCESS;
    }
}
