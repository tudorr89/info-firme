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
        'type',
        'website',
        'parent_country',
        'mark',
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

    public function legalRepresentatives(): HasMany
    {
        return $this->hasMany(LegalRepresentative::class, 'registration', 'reg_com');
    }

    public function naturalPersonRepresentatives(): HasMany
    {
        return $this->hasMany(NaturalPersonRepresentative::class, 'registration', 'reg_com');
    }

    public function euBranches(): HasMany
    {
        return $this->hasMany(EUBranch::class, 'registration', 'reg_com');
    }
}
