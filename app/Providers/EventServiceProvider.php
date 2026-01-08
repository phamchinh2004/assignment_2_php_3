<?php

namespace App\Providers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\MoneyDeposited;
use App\Events\PermissionRevoked;
use App\Events\StaffLocked;
use App\Events\UserJoinChat;
use App\Events\UserLocked;
use App\Events\UserSentMessage;
use App\Listeners\HandleMessageRead;
use App\Listeners\HandleMessageSent;
use App\Listeners\HandleMoneyDeposited;
use App\Listeners\HandlePermissionRevoked;
use App\Listeners\HandleStaffLocked;
use App\Listeners\HandleUserJoinChat;
use App\Listeners\HandleUserLocked;
use App\Listeners\HandleUserSentMessage;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserJoinChat::class => [
            HandleUserJoinChat::class,
        ],
        MessageSent::class => [
            HandleMessageSent::class,
        ],
        MessageRead::class => [
            HandleMessageRead::class,
        ],
        UserSentMessage::class => [
            HandleUserSentMessage::class,
        ],
        MoneyDeposited::class => [
            HandleMoneyDeposited::class,
        ],
        UserLocked::class => [
            HandleUserLocked::class,
        ],
        StaffLocked::class => [
            HandleStaffLocked::class,
        ],
        PermissionRevoked::class => [
            HandlePermissionRevoked::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
