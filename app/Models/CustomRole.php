<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\User;

class CustomRole extends SpatieRole
{

    /**
     * Get the user who created the role.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the role.
     */
    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
