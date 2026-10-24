<?php

namespace App\Models;

use App\Enums\CifRemovalStatus;
use App\Services\BannedCifService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A request to remove a CIF from the application.
 *
 * Requests are submitted from the public form and reviewed in the admin panel.
 * Only an approved request makes the CIF disappear: it is then treated exactly
 * like a banned CIF (skipped by the importer, hidden from search, the company
 * page and the API).
 *
 * @property int $id
 * @property string $cui
 * @property string|null $company_name
 * @property string $requester_name
 * @property string $requester_email
 * @property string $reason
 * @property CifRemovalStatus $status
 * @property string|null $ip_address
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 */
class CifRemovalRequest extends Model
{
    protected $fillable = [
        'cui',
        'company_name',
        'requester_name',
        'requester_email',
        'reason',
        'status',
        'ip_address',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CifRemovalStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The banned-CIF list is cached, so it has to be dropped whenever a request
     * changes state — including an edit made directly in the admin panel.
     */
    protected static function booted(): void
    {
        $flush = static fn () => BannedCifService::flush();

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * @param  Builder<CifRemovalRequest>  $query
     * @return Builder<CifRemovalRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CifRemovalStatus::Pending);
    }

    /**
     * @param  Builder<CifRemovalRequest>  $query
     * @return Builder<CifRemovalRequest>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CifRemovalStatus::Approved);
    }

    public function isPending(): bool
    {
        return $this->status === CifRemovalStatus::Pending;
    }
}
