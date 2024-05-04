<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Info extends Model
{
    use HasFactory;

    protected $guarded= [];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'company_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
