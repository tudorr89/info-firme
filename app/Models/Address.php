<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'company_id',
        'created_at',
        'updated_at',
        'id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
