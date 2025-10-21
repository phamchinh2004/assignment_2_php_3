<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MoneyDeposited implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $amount;
    public $newBalance;
    public $transactionType;
    public $adminName;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $amount, $newBalance, $transactionType, $adminName)
    {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->newBalance = $newBalance;
        $this->transactionType = $transactionType;
        $this->adminName = $adminName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'money.deposited';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'amount' => $this->amount,
            'new_balance' => $this->newBalance,
            'transaction_type' => $this->transactionType,
            'admin_name' => $this->adminName,
            'message' => $this->transactionType === 'normal' 
                ? "Bạn đã được nạp {$this->amount}$ bởi {$this->adminName}" 
                : "Bạn đã nhận {$this->amount}$ tiền thưởng từ {$this->adminName}",
        ];
    }
}
