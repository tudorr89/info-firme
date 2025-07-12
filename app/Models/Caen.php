<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caen extends Model
{
    protected $guarded = [];

    public function version(): BelongsTo
    {
        return $this->belongsTo(CaenVersion::class, 'code');
    }
}
