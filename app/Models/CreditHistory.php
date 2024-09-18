<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_credit_id',
        'transaction_id',
        'amount',
        'status',
        'receipt_url'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
