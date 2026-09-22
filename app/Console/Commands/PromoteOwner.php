<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteOwner extends Command
{
    protected $signature = 'owner:promote {account : Username or email of an existing admin} {--force : Skip confirmation}';

    protected $description = 'Promote an existing admin account to the system owner role';

    public function handle(): int
    {
        $account = (string) $this->argument('account');
        $user = User::query()
            ->where('username', $account)
            ->orWhere('email', $account)
            ->first();

        if (!$user) {
            $this->error('Account not found.');
            return self::FAILURE;
        }

        if ($user->role === User::ROLE_OWNER) {
            $this->info('This account is already an owner.');
            return self::SUCCESS;
        }

        if ($user->role !== User::ROLE_ADMIN) {
            $this->error('Only an existing admin account can be promoted to owner.');
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm("Promote {$user->username} to owner?")) {
            $this->warn('No changes were made.');
            return self::SUCCESS;
        }

        $user->forceFill(['role' => User::ROLE_OWNER])->save();

        $this->info("{$user->username} is now an owner.");
        return self::SUCCESS;
    }
}
