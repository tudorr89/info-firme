<?php

namespace App\Mail;

use App\Models\CifRemovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The decision on a GDPR removal request.
 *
 * `review_note` is deliberately not included: the admin panel labels it as an
 * internal note (the rejection modal calls it "notă internă"), so it must not
 * travel to the data subject. The outcome itself is what Art. 12 asks to
 * communicate.
 */
class CifRemovalRequestDecided extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CifRemovalRequest $request) {}

    public function envelope(): Envelope
    {
        $outcome = $this->request->status->isApproved() ? 'aprobată' : 'respinsă';

        return new Envelope(
            subject: 'Cererea ta de ștergere a fost '.$outcome.' (CIF '.$this->request->cui.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.cif-removal.decided',
            with: [
                'approved' => $this->request->status->isApproved(),
            ],
        );
    }
}
