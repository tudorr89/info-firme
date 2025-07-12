<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaenCompany extends Model
{
    protected $guarded = [];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'reg_com','registration');
    }

    public function details(): BelongsTo
    {
        return $this->belongsTo(Caen::class, 'class', 'code');
    }
}
