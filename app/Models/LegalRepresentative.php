<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalRepresentative extends Model
{
    protected $fillable = [
        'company_id',
        'registration',
        'person_name',
        'role',
        'birth_date',
        'birth_location',
        'birth_county',
        'birth_country',
        'current_location',
        'current_county',
        'current_country',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
