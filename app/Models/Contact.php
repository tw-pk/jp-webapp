<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'email',
        'phone',
        'address_home',
        'address_office'
    ];

    public function fullName(){
        return $this->firstname." ".$this->lastname;
    }

    public function userProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserProfile::class, 'contact_id');
    }
}
