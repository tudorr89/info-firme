<?php

namespace App\Services;

use App\Enums\CifRemovalStatus;
use App\Models\CifRemovalRequest;
use App\Models\User;

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
     * @return array<string, int>
     */
    public static function approve(CifRemovalRequest $request, ?User $reviewer = null, ?string $note = null): array
    {
        $request->update([
            'status' => CifRemovalStatus::Approved,
            'review_note' => $note,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
        ]);

        return CompanyDeletionService::deleteByCui($request->cui);
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
