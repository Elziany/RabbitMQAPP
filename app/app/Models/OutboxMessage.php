<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    protected $table = 'outbox_messages';
    protected $guarded = [];
}
