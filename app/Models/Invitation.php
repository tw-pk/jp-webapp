<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'role',
        'member_id',
        'registered',
        'number',
        'user_id'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userProfile()
    {
        return $this->belongsTo(UserProfile::class, 'user_id');
    }

    public function roleInfo()
    {
        return $this->belongsTo(Role::class, 'role', 'id');
    }

    public function invitationAccept()
    {
        return $this->belongsTo(User::class, 'member_id', 'id');
    }

    public function userAvatar()
    {
        return $this->invitationAccept->profile->avatar ?? null;
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_member', 'invitation_id', 'team_id');
    }

    public function assignedNumbers()
    {
        return $this->hasMany(AssignNumber::class, 'invitation_id');
    }
}
