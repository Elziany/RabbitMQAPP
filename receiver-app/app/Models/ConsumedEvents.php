<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumedEvents extends Model
{
    protected $table = 'consumed_events';
    protected $fillable = ['event_uuid' , 'consumed_at'];
}
