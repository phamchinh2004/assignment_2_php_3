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

class SpecialOrderWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $frozenOrder;
    public $hoursPassed;
    public $remainingHours;
    public $warningType;
    public $warningThreshold;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Frozen_order $frozenOrder, int $hoursPassed, int $remainingHours, string $warningType, int $warningThreshold)
    {
        $this->user = $user;
        $this->frozenOrder = $frozenOrder;
        $this->hoursPassed = $hoursPassed;
        $this->remainingHours = $remainingHours;
        $this->warningType = $warningType;
        $this->warningThreshold = $warningThreshold;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $title = $this->warningType === 'first'
            ? "Cảnh báo đơn hàng còn {$this->warningThreshold} giờ đến hạn - " . config('app.name')
            : "Cảnh báo lần cuối còn {$this->warningThreshold} giờ đến hạn phạt - " . config('app.name');

        return new Envelope(
            subject: $title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.special_order_warning',
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
