<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cui',
        'reg_com',
        'euid',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
    ];

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function info()
    {
        return $this->hasOne(Info::class);
    }

    public function status()
    {
        return $this->hasMany(Status::class);
    }
}
