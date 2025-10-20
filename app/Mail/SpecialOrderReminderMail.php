<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Frozen_order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SpecialOrderReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $frozenOrder;
    public $hoursPassed;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Frozen_order $frozenOrder, int $hoursPassed)
    {
        $this->user = $user;
        $this->frozenOrder = $frozenOrder;
        $this->hoursPassed = $hoursPassed;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nhắc nhở đơn hàng đặc biệt chưa phân phối - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.special_order_reminder',
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
