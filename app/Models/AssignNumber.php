<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignNumber extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'invitation_id', 'phone_number', 'created_at'];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'invitation_id');
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class, 'invitation_id', 'id');
    }
}
