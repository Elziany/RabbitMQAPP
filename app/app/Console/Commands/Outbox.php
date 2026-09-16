<?php

namespace App\Console\Commands;

use App\Services\OutboxMessageService;
use App\Services\RabbitMQPublisher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:outbox')]
#[Description('Command description')]
class Outbox extends Command
{

     public function handle(
        OutboxMessageService $outbox,
        RabbitMQPublisher $publisher
    ): int {

        $this->info('Outbox publisher started...');

        while (true) {

            $messages = $outbox->getPending(100);

            foreach ($messages as $message) {

                try {

                    $publisher->publish([
                        'event_type' => $message->event_type,
                        'payload' => json_decode($message->payload, true),
                        'event_uuid' => $message->event_uuid,
                    ]);

                    $outbox->markAsDispatched($message->id);

                } catch (\Throwable $e) {

                    $this->error($e->getMessage());
                }
            }

            sleep(1);
        }

        return self::SUCCESS;
    }
}
