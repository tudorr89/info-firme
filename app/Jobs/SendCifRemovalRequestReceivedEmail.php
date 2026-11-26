<?php

namespace App\Jobs;

use App\Mail\CifRemovalRequestReceived;
use App\Models\CifRemovalRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Confirms to the requester that their GDPR removal request was received.
 *
 * Queued so the public form never waits on the mail server, and so a delivery
 * failure is retried instead of being shown to the visitor.
 */
class SendCifRemovalRequestReceivedEmail implements ShouldQueue
{
    use Queueable;

    /**
     * A spam request can be deleted before the job runs; that is not a failure.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * One attempt per try: the mail server either takes the message or it does
     * not, and a request older than a few minutes is not worth retrying forever.
     */
    public int $tries = 3;

    public function __construct(public CifRemovalRequest $request) {}

    public function handle(): void
    {
        Mail::to($this->request->requester_email)
            ->send(new CifRemovalRequestReceived($this->request));
    }
}
