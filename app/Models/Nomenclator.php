<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function status()
    {
        return $this->hasOne(Status::class);
    }
}
