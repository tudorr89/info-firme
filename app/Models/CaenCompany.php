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
        return $this->resolveCaen()?->setRelation('version', $this->version);
    }

    /**
     * Resolve the CAEN definition matching both the code and the CAEN version,
     * falling back to the newest definition of that code.
     */
    private function resolveCaen(): ?Caen
    {
        return Caen::where('class', $this->code)
            ->where('version', $this->version)
            ->first()
            ?? Caen::where('class', $this->code)->orderByDesc('version')->first()
            ?? Caen::find($this->code);
    }
}
