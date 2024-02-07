<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneSetting extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'phone_number', 'fwd_incoming_call', 'unanswered_fwd_call', 'call_routing_emails', 'incoming_caller_id', 'outbound_caller_id', 'vunanswered_fwd_call', 'vemail_id', 'voice_message', 'transcription'];
}
