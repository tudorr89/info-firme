<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration',
        'status',
    ];

    protected $hidden = [
        'id',
        'registration',
        'created_at',
        'updated_at',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'reg_com', 'registration');
    }

    public function details(): BelongsTo
    {
        return $this->belongsTo(Nomenclator::class, 'status','code');
    }
}
