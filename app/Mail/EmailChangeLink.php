<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('settings.email_change.mail.subject', ['name' => config('marche.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.email-change-link',
            with: [
                'validityHours' => intdiv(config('marche.registration_link_ttl'), 60),
            ],
        );
    }
}
