<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
      'owner_id',
      'member_email',
      'registered'
    ];

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'owner_id');
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'member_id');
    }

    public function memberRegistered(): bool
    {
        $user = User::where('member_email', $this->email)->first();
        if(!$user){
            return false;
        }else{
            return true;
        }
    }
}
