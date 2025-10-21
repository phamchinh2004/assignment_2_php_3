<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChatNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $userMessage;
    public $conversationId;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $userMessage, $conversationId)
    {
        $this->user = $user;
        $this->userMessage = $userMessage;
        $this->conversationId = $conversationId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tin nhắn mới từ ' . ($this->user->full_name ?? $this->user->username),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.chat_notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
