<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaenCompany extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'id',
        'registration',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['caen_details'];

    /**
     * The definition is resolved once per row: `toArray()` reads the appended
     * attribute, and the API serializes the same row twice (payload + cache).
     */
    private ?Caen $resolvedCaen = null;

    private bool $caenResolved = false;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'registration', 'reg_com');
    }

    public function details(): BelongsTo
    {
        return $this->belongsTo(Caen::class, 'code', 'class');
    }

    public function getCaenDetailsAttribute(): ?Caen
    {
        if (! $this->caenResolved) {
            $this->resolvedCaen = $this->resolveCaen();
            $this->caenResolved = true;
        }

        return $this->resolvedCaen?->setRelation('version', $this->version);
    }

    /**
     * Resolve the CAEN definition matching both the code and the CAEN version,
     * falling back to the newest definition of that code.
     *
     * The eager-loaded `details` relation matches on the code only, so it is
     * used only when it happens to be the right version; otherwise this would
     * add 1–3 queries per row on top of the eager load.
     */
    private function resolveCaen(): ?Caen
    {
        $loaded = $this->relationLoaded('details') ? $this->getRelation('details') : null;

        if ($loaded instanceof Caen && (int) $loaded->version === (int) $this->version) {
            return $loaded;
        }

        return Caen::where('class', $this->code)
            ->where('version', $this->version)
            ->first()
            ?? Caen::where('class', $this->code)->orderByDesc('version')->first()
            ?? Caen::find($this->code);
    }
}
