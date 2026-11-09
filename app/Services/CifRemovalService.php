<?php

namespace App\Services;

use App\Enums\CifRemovalStatus;
use App\Models\CifRemovalRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
     * @return array<string, int>
     */
    public static function approve(CifRemovalRequest $request, ?User $reviewer = null, ?string $note = null): array
    {
        return DB::transaction(function () use ($request, $reviewer, $note): array {
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

            return CompanyDeletionService::deleteByCui($locked->cui);
        });
    }

    public static function reject(CifRemovalRequest $request, ?User $reviewer = null, ?string $note = null): void
    {
        $request->update([
            'status' => CifRemovalStatus::Rejected,
            'review_note' => $note,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
        ]);
    }
}
