<?php

namespace App\Services;

use App\Models\OutboxMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutboxMessageService
{
   
    public function create(
        string $eventType,
        array $payload,
    ): OutboxMessage {
        return DB::transaction(function () use (
            $eventType,
            $payload
        ) {
            return OutboxMessage::create([
                'event_type' => $eventType,
                'payload' => json_encode($payload),
                'event_uuid' => (string) Str::uuid(),
            ]);
        });
    }

    public function getPending(int $limit = 50): Collection
    {
        return OutboxMessage::query()
            ->whereNull('published_at')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function markAsDispatched(int $id): bool
    {
        return OutboxMessage::query()
            ->whereKey($id)
            ->update([
                'published_at' => now(),
            ]) > 0;
    }
}
