<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ReceiveNotification;

Artisan::command('inspire', function () {
 ReceiveNotification::dispatch();
})->purpose('Display an inspiring quote');
