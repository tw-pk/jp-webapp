<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'to',
        'from',
        'user_id',
        'duration',
        'status',
        'direction',
        'date_time',
        'sid',
        'contact_id',
        'price',
        'country_price',
        'conference_name',
        'mobile_call_sid',        
        'total_price'

    ];


    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
