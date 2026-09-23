<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatReadService
{
    public function markConversationRead(Conversation $conversation, int $readerId): int
    {
        $readAt = now();
        $inserted = 0;

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->unreadFor($readerId)
            ->select('id')
            ->chunkById(500, function ($messages) use ($readerId, $readAt, &$inserted) {
                $inserted += DB::table('message_reads')->insertOrIgnore(
                    $messages->map(fn (Message $message) => [
                        'message_id' => $message->id,
                        'user_id' => $readerId,
                        'read_at' => $readAt,
                    ])->all()
                );
            });

        return $inserted;
    }

    public function markMessageRead(int $messageId, Conversation $conversation, int $readerId): bool
    {
        $message = Message::query()
            ->whereKey($messageId)
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $readerId)
            ->first(['id']);

        if (!$message) {
            return false;
        }

        return DB::table('message_reads')->insertOrIgnore([
            'message_id' => $messageId,
            'user_id' => $readerId,
            'read_at' => now(),
        ]) > 0;
    }

    /** Receipt shown to the sender: customer -> assigned operator, operator -> customer. */
    public function sentReadStatuses(Collection $messages, Conversation $conversation): array
    {
        if ($messages->isEmpty()) {
            return [];
        }

        $recipients = array_filter([(int) $conversation->user_id, (int) $conversation->staff_id]);
        $reads = DB::table('message_reads')
            ->whereIn('message_id', $messages->pluck('id')->all())
            ->whereIn('user_id', $recipients)
            ->get(['message_id', 'user_id'])
            ->mapWithKeys(fn ($read) => [$read->message_id . ':' . $read->user_id => true]);

        $statuses = [];
        foreach ($messages as $message) {
            $recipientId = (int) $message->sender_id === (int) $conversation->user_id
                ? (int) $conversation->staff_id
                : (int) $conversation->user_id;
            $statuses[$message->id] = $recipientId > 0
                && $reads->has($message->id . ':' . $recipientId);
        }

        return $statuses;
    }
}
