<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Nomenclator extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
    ];
    public function status(): HasOne
    {
        return $this->hasOne(Status::class);
    }
}
