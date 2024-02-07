<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pmId',
        'default',
        'card_number',
        'expiry_month',
        'expiry_year',
        'card_name',
        'card_brand',
        'card_email'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
