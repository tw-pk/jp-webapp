<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    public function assignNumbers()
    {
        return $this->hasMany(AssignNumber::class, 'team_id');
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class, 'invitation_id', 'id');
    }
}
