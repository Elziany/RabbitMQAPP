<?php
namespace App\Handlers;
class NotificationHandler
{
    public function handle(array $data): void
    {
        \Log::info('Received notification:', $data);
    }
}