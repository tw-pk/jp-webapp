<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwilioOutboundPrices extends Model
{
    use HasFactory;
    protected $table = 'twilio_countries_price_lists';
    protected $fillable = ['ISO', 'Country', 'Description', 'Price'];
}
