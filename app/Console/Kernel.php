<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:reset-daily-user-data')->dailyAt('00:00');
        
        // Kiểm tra đơn hàng đặc biệt chưa phân phối mỗi phút
        $schedule->command('orders:check-special-reminder')->everyMinute();
        
        // Tự động chuyển trạng thái đơn hàng mỗi 5 phút
        $schedule->command('orders:auto-process-status')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
