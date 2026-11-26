<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The registration status a company currently has, as reported by ONRC.
 *
 * `registration` is the trade-register number (used instead of the company id
 * because imports are keyed by it) and `status` is the ONRC status code, whose
 * description lives in `nomenclators`.
 *
 * @property int $id
 * @property string $registration
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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
        return $this->belongsTo(Nomenclator::class, 'status', 'code');
    }
}
