<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StatusService
{
    /**
     * Get the user status based on the last login time.
     *
     * @param int $userId
     * @return string
     */

    public static function getUserStatus($userId = null)
    {
        $user = $userId ? User::find($userId) : Auth::user();
        if (!$user || !$user->activity_at) {
            return 'Offline';
        }

        $curtime = Carbon::now();
        $dateTime = Carbon::parse($user->activity_at);
        //diffInHours
        $lastLoginDifference = $curtime->diffInMinutes($dateTime);

        if ($lastLoginDifference <= 30) {
            return 'Online';
        } elseif ($lastLoginDifference > 30 && $lastLoginDifference <= 60) {
            return 'Away';
        } else {
            return 'Offline';
        }
    }

}
