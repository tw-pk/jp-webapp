<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DialerSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country_outbound_calls',
        'number_outbound_calls',
        'number_outbound_sms',
        'active_noise_cancellation'
    ];
}
