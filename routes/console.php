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

Schedule::command('backup:snapshot db')
    ->everyTenMinutes()
    ->timezone(config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh'))
    ->withoutOverlapping(30);

Schedule::command('backup:snapshot project')
    ->dailyAt(config('backup.schedule.project_backup_time', '04:00'))
    ->timezone(config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh'))
    ->withoutOverlapping(180);
