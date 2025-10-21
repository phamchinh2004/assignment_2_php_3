<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DepositNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $amount;
    public $newBalance;
    public $transactionType;
    public $adminName;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $amount, $newBalance, $transactionType, $adminName)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->newBalance = $newBalance;
        $this->transactionType = $transactionType;
        $this->adminName = $adminName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->transactionType === 'normal' 
            ? 'Thông báo nạp tiền vào tài khoản' 
            : 'Thông báo nhận tiền thưởng';
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.deposit_notification',
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
