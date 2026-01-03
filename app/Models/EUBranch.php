<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EUBranch extends Model
{
    protected $table = 'e_u_branches';

    protected $hidden = [
        'id',
        'company_id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'company_id',
        'registration',
        'branch_name',
        'branch_type',
        'euid',
        'tax_code',
        'country',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
