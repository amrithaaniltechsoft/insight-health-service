<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $enquiry;

    public function __construct(array $enquiry)
    {
        $this->enquiry = $enquiry;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Enquiry from {$this->enquiry['first_name']} {$this->enquiry['last_name']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enquiry',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
