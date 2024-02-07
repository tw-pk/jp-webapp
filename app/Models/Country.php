<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code_3', 'code_2', 'numeric_code', 'phone_code', 'capital', 'currency', 'currency_name', 'currency_symbol', 'tld', 'native', 'region', 'subregion', 'latitude', 'longitude', 'emoji', 'emojiU'];


    public function timezones()
    {
        return $this->hasMany(Timezone::class);
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }
}
