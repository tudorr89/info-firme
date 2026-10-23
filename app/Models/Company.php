<?php

namespace App\Models;

use App\Services\CompanyStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Status|null $status
 * @property-read int|null $is_active
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cui',
        'reg_com',
        'euid',
        'status',
        'registration_date',
        'type',
        'website',
        'parent_country',
        'mark',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
        ];
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function info(): HasOne
    {
        return $this->hasOne(Info::class);
    }

    public function status(): HasOne
    {
        return $this->hasOne(Status::class, 'registration', 'reg_com');
    }

    public function caen(): HasMany
    {
        return $this->HasMany(CaenCompany::class, 'registration', 'reg_com');
    }

    public function legalRepresentatives(): HasMany
    {
        return $this->hasMany(LegalRepresentative::class, 'registration', 'reg_com');
    }

    public function naturalPersonRepresentatives(): HasMany
    {
        return $this->hasMany(NaturalPersonRepresentative::class, 'registration', 'reg_com');
    }

    public function euBranches(): HasMany
    {
        return $this->hasMany(EUBranch::class, 'registration', 'reg_com');
    }

    /**
     * Order the result so that the company that is still operating comes first.
     *
     * A CIF can be reused: the ONRC keeps the old, deregistered company and a
     * new company may be registered with the same CIF. This adds an `is_active`
     * column to the select and sorts by it, so `first()` returns the company
     * that is currently operating.
     */
    public function scopePreferActive(Builder $query): Builder
    {
        $codes = CompanyStatusService::activeCodes();
        $placeholders = implode(', ', array_fill(0, count($codes), '?'));

        return $query
            ->select('companies.*')
            ->selectRaw(
                "EXISTS (
                    SELECT 1 FROM statuses
                    WHERE statuses.registration = companies.reg_com
                      AND statuses.status IN ({$placeholders})
                ) AS is_active",
                $codes
            )
            ->orderByDesc('is_active');
    }

    /**
     * Limit the query to companies having one of the given status codes.
     *
     * @param  array<int, int>  $codes
     */
    public function scopeWithStatus(Builder $query, array $codes): Builder
    {
        return $query->whereHas('status', fn (Builder $status) => $status->whereIn('status', $codes));
    }

    /**
     * Limit the query to what may be presented publicly: obsolete companies are
     * excluded when `companies.hide_obsolete` is enabled.
     */
    public function scopePresentable(Builder $query): Builder
    {
        if (! CompanyStatusService::hidesObsolete()) {
            return $query;
        }

        return $query->whereDoesntHave(
            'status',
            fn (Builder $status) => $status->whereIn('status', CompanyStatusService::obsoleteCodes())
        );
    }
}
