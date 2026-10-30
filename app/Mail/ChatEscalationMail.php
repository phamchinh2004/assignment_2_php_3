<?php

namespace App\Mail;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

class ChatEscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $conversationId;
    public $userId;
    public $autoReplyMessage;
    public $afterMinutes;

    public function __construct($conversationId, $userId, $autoReplyMessage, $afterMinutes)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->autoReplyMessage = $autoReplyMessage;
        $this->afterMinutes = $afterMinutes;
    }

    public function envelope(): Envelope
    {
        $conversation = Conversation::find($this->conversationId);
        $customer = $conversation?->user ?? User::find($this->userId);
        $customerName = $customer?->full_name ?: ($customer?->username ?: "Khách hàng #{$this->userId}");
        $timeText = $this->afterMinutes > 0 ? "sau {$this->afterMinutes} phút" : 'nhân viên offline';

        return new Envelope(
            subject: '[' . config('app.name') . "] [Cảnh báo] {$customerName} đang chờ phản hồi ({$timeText}) - #{$this->conversationId}",
        );
    }

    public function content(): Content
    {
        $conversation = Conversation::with(['user', 'staff'])->find($this->conversationId);
        $customer = $conversation?->user ?? User::find($this->userId);
        $staff = $conversation?->staff;

        $lastManagementReplyId = Message::query()
            ->where('conversation_id', $this->conversationId)
            ->where(fn ($query) => $query->whereNull('kind')->orWhere('kind', '!=', 'auto_reply'))
            ->whereHas('sender', fn ($query) => $query->whereIn('role', User::MANAGEMENT_ROLES))
            ->max('messages.id') ?? 0;

        $customerMessages = Message::query()
            ->where('conversation_id', $this->conversationId)
            ->where('sender_id', $this->userId)
            ->when($lastManagementReplyId > 0, fn ($query) => $query->where('id', '>', $lastManagementReplyId))
            ->orderBy('id', 'asc')
            ->take(10)
            ->get();

        $firstUnansweredMessageAt = $customerMessages->first()?->created_at;

        $publicId = $conversation?->public_id;
        $chatUrl = Route::has('chat-panel')
            ? route('chat-panel', array_filter(['conversation' => $publicId]))
            : url('/admin/chat-panel' . ($publicId ? '?conversation=' . $publicId : ''));

        return new Content(
            view: 'emails.chat_escalation',
            text: 'emails.text.chat_escalation',
            with: [
                'conversationId' => $this->conversationId,
                'conversation' => $conversation,
                'userId' => $this->userId,
                'customer' => $customer,
                'staff' => $staff,
                'customerMessages' => $customerMessages,
                'firstUnansweredMessageAt' => $firstUnansweredMessageAt,
                'autoReplyMessage' => $this->autoReplyMessage,
                'afterMinutes' => $this->afterMinutes,
                'chatUrl' => $chatUrl,
            ],
        );
    }
}
