<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwilioPasswordVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sid',
        'channel',
        'otp',
        'expiry_at',
        'verified'
    ];
}
