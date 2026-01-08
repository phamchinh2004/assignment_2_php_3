<?php

namespace App\Jobs;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastMessageSent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageId;

    /**
     * Create a new job instance.
     */
    public function __construct($messageId)
    {
        // CHỈ LƯU ID - KHÔNG QUERY!
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Broadcast với messageId (không cần query lại, event sẽ tự query)
        broadcast(new MessageSent($this->messageId))->toOthers();
    }
}
