<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'phone_number_sid',
        'country_code',
        'country',
        'city',
        'state',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
