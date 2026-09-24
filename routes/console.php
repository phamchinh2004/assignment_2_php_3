<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run --only-db --disable-notifications')
    ->dailyAt(config('backup.schedule.backup_time', '02:00'))
    ->timezone(config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh'))
    ->withoutOverlapping();

Schedule::command('backup:run --disable-notifications')
    ->weeklyOn(
        config('backup.schedule.full_backup_day', 0),
        config('backup.schedule.full_backup_time', '04:00')
    )
    ->timezone(config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh'))
    ->withoutOverlapping();

Schedule::command('backup:clean --disable-notifications')
    ->dailyAt(config('backup.schedule.cleanup_time', '03:00'))
    ->timezone(config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh'))
    ->withoutOverlapping();
