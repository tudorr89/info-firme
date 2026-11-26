<?php

namespace App\Jobs;

use App\Mail\CifRemovalRequestDecided;
use App\Models\CifRemovalRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the requester whether their GDPR removal request was approved or
 * rejected.
 *
 * Dispatched by `CifRemovalService` after the state change has been committed,
 * so the mail can never announce a decision the database did not keep.
 */
class SendCifRemovalDecisionEmail implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    public function __construct(public CifRemovalRequest $request) {}

    public function handle(): void
    {
        Mail::to($this->request->requester_email)
            ->send(new CifRemovalRequestDecided($this->request));
    }
}
