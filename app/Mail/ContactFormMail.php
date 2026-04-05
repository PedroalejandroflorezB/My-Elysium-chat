<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $message
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📬 Nuevo mensaje de contacto - ' . config('app.name'),
            replyTo: [
                new \Illuminate\Mail\Mailables\Address($this->senderEmail, $this->senderName)
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
        );
    }
}
