<?php

namespace App\Models;

use App\Http\Resources\ApiTwilioConversationResource;
use App\Http\Resources\TwilioConversationResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contact_id',
        'sid',
        'unique_name',
        'friendly_name'
    ];

    public function contact(){
        return $this->belongsTo(Contact::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
