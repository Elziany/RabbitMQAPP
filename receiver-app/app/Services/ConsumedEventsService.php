<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ConsumedEventsService
{
    
    public function insertOrIgnore(string $eventUuid): bool
    {
        return DB::table('consumed_events')->insertOrIgnore(['event_uuid' => $eventUuid , 'consumed_at' => now()]) > 0;
    }
}