<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\User_spin_progress;
use Illuminate\Console\Command;

class ResetDailyUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-daily-user-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset daily user fields and spin progress';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::query()->update([
            'distribution_today' => 0,
            'todays_discount' => 0,
            'count_withdrawals' => 0
        ]);

        // Reset bảng user_spin_progresses
        User_spin_progress::query()->update([
            'current_spin' => 0
        ]);

        $this->info('Daily reset completed successfully.');
    }
}
