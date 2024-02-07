<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar',
        'bio',
        'abilities',
        'organization',
        'phone_number',
        'address',
        'state',
        'country',
        'zip_code',
        'timezone',
        'phone_number'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function member()
    {
        return $this->hasOne(Member::class, 'user_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
