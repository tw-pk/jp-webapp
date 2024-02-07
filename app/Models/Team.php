<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'phone', 'member', 'status'];

    public function members()
    {
        return $this->belongsToMany(Invitation::class, 'team_members', 'team_id', 'invitation_id');
    }

    public function assignedNumbers()
    {
        return $this->hasMany(AssignNumber::class, 'team_id');
    }
}
