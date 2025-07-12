<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cui',
        'reg_com',
        'euid',
        'status',
        'registration_date',
        'type'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
    ];

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function info(): HasOne
    {
        return $this->hasOne(Info::class);
    }

    public function status(): HasOne
    {
        return $this->hasOne(Status::class, 'registration', 'reg_com');
    }

    public function caen(): HasMany
    {
        return $this->HasMany(CaenCompany::class, 'registration', 'reg_com');
    }
}
