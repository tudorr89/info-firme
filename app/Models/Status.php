<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'status',
    ];

    protected $hidden = [
        'id',
        'company_id',
        'created_at',
        'updated_at',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function details()
    {
        return $this->belongsTo(Nomenclator::class, 'status','code');
    }
}
