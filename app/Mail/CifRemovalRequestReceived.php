<?php

namespace App\Mail;

use App\Models\CifRemovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the operator that a new CIF removal request was submitted.
 *
 * The requester's address is set as `Reply-To`, so replying from the inbox goes
 * straight to the person who made the request.
 */
class CifRemovalRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CifRemovalRequest $request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cerere de ștergere CIF '.$this->request->cui,
            replyTo: [
                new Address($this->request->requester_email, $this->request->requester_name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.cif-removal-request-received',
            with: [
                'cifRequest' => $this->request,
                'reviewUrl' => url('/admin/cif-removal-requests/'.$this->request->getKey().'/edit'),
            ],
        );
    }
}
