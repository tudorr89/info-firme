<?php

namespace App\Services;

use App\Enums\CifRemovalStatus;
use App\Jobs\SendCifRemovalDecisionEmail;
use App\Models\CifRemovalRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Approving or rejecting a CIF removal request.
 *
 * Approval is what actually enforces the request: it marks the row as approved
 * (which makes `BannedCifService` pick the CIF up) and deletes the company data
 * that is already in the database. The banned-CIF cache is flushed by the model
 * events on `CifRemovalRequest`, so every path that changes the state — this
 * service or a manual edit in the admin panel — stays consistent.
 */
final class CifRemovalService
{
    /**
     * Approving is idempotent: the row is locked and re-checked inside the
     * transaction, so a double click (or two administrators at once) cannot
     * approve the same request twice and run the deletion a second time.
     *
     * Both decisions queue the notification to the requester after the state
     * change has been committed, so the mail can never announce a decision the
     * database did not keep.
     *
     * @return array<string, int>
     */
    public static function approve(CifRemovalRequest $request, ?User $reviewer = null, ?string $note = null): array
    {
        $approved = false;

        $summary = DB::transaction(function () use ($request, $reviewer, $note, &$approved): array {
            $locked = CifRemovalRequest::query()
                ->lockForUpdate()
                ->find($request->getKey());

            if (! $locked instanceof CifRemovalRequest || ! $locked->isPending()) {
                return CompanyDeletionService::emptySummary();
            }

            $locked->update([
                'status' => CifRemovalStatus::Approved,
                'review_note' => $note,
                'reviewed_by' => $reviewer?->getKey(),
                'reviewed_at' => now(),
            ]);

            $approved = true;

            return CompanyDeletionService::deleteByCui($locked->cui);
        });

        // After the commit, and only for a real transition: a second approve of
        // an already-approved request does not announce the decision twice.
        if ($approved) {
            self::notifyRequester($request->refresh());
        }

        return $summary;
    }

    public static function reject(CifRemovalRequest $request, ?User $reviewer = null, ?string $note = null): void
    {
        // Rejecting an already-rejected request is a no-op for the requester;
        // rejecting an approved one is a real reversal and does notify them.
        $changed = $request->status !== CifRemovalStatus::Rejected;

        $request->update([
            'status' => CifRemovalStatus::Rejected,
            'review_note' => $note,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
        ]);

        if ($changed) {
            self::notifyRequester($request);
        }
    }

    /**
     * A queue outage is reported, never thrown: the decision is already
     * persisted, so it must not look failed to the administrator.
     */
    private static function notifyRequester(CifRemovalRequest $request): void
    {
        try {
            SendCifRemovalDecisionEmail::dispatch($request);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
