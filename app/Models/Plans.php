<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;

class Plans extends Model
{
    use HasFactory;


    /**
     * The attributes that are mass assignable.
     *
     * @var array

     */
    protected $fillable = [
        'name',
        'slug',
        'stripe_price',
        'price',
        'interval',
    ];

    /**
     * Write code on Method
     *
     * @return Response|string ()
     */
    public function getRouteKeyName(): Response|string
    {
        return 'slug';
    }

}
