<?php

namespace App\Mail;

use App\Models\CifRemovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Receipt for a GDPR removal request.
 *
 * It is sent from a queued job, never inline: a slow or broken mail server must
 * not make the public form slow, and a delivery failure must never lose the
 * request that was just stored.
 */
class CifRemovalRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CifRemovalRequest $request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Am primit cererea ta de ștergere (CIF '.$this->request->cui.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.cif-removal.received',
        );
    }
}
