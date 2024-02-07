<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit',
        'user_id',
        'threshold_value',
        'threshold_enabled',
        'recharge_value'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
