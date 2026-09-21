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

class HighValueOrderPenaltyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $frozenOrder;
    public $hoursPassed;
    public $penaltyAmount;
    public $orderValue;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Frozen_order $frozenOrder, int $hoursPassed, float $penaltyAmount)
    {
        $this->user = $user;
        $this->frozenOrder = $frozenOrder;
        $this->hoursPassed = $hoursPassed;
        $this->penaltyAmount = $penaltyAmount;
        $this->orderValue = $frozenOrder->snapshot_order_value ?? 0.0;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name') . '] Cập nhật phí xử lý đơn hàng ' . ($this->frozenOrder->display_order_code ?? $this->frozenOrder->order_id),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.high_value_order_penalty',
            text: 'emails.text.high_value_order_penalty',
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
