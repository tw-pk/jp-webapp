<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwilioCountry extends Model
{
    use HasFactory;

    protected $fillable = ['country_code', 'country', 'uri', 'beta', 'subresource_uris', 'subresource_type'];
}
