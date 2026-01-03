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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'reg_com', 'registration');
    }

    public function details(): BelongsTo
    {
        return $this->belongsTo(Caen::class, 'code', 'id');
    }
}
