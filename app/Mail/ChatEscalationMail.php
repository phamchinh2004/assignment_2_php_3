<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

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
        return new Envelope(
            subject: '[' . config('app.name') . "] Cuộc trò chuyện #{$this->conversationId} đang chờ phản hồi",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.chat_escalation',
            text: 'emails.text.chat_escalation',
            with: [
                'conversationId' => $this->conversationId,
                'userId' => $this->userId,
                'autoReplyMessage' => $this->autoReplyMessage,
                'afterMinutes' => $this->afterMinutes,
            ],
        );
    }
}
