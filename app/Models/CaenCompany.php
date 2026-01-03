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
        'updated_at'
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
        // Try matching by class first (newer CAEN structure)
        $caen = Caen::where('class', $this->code)->first();

        // Fall back to matching by id (older CAEN structure)
        if (!$caen) {
            $caen = Caen::find($this->code);
        }

        return $caen;
    }
}
