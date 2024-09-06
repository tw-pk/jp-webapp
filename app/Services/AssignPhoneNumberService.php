<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;

class AssignPhoneNumberService
{

    public function getAssignPhoneNumbers($userId = null)
    {
        $phoneNumberArray = [];
    
        $user = $userId ? User::find($userId) : Auth::user();
        if (!$user) {
            return $phoneNumberArray;
        }
        $phoneNumbers = $user->numbers->pluck('phone_number');
        if (!empty($phoneNumbers)) {
            $phoneNumberArray = array_merge($phoneNumberArray, $phoneNumbers->toArray());
        }

        $invitation = Invitation::with(['assignedNumbers', 'assignedInvitationTeam'])->where('member_id', $user->id)->first();
        if ($invitation) {
            $invitation->can_have_new_number == 0 ? $phoneNumberArray[] = $invitation->number : null;

            $assignedPhoneNumbers = $invitation->assignedNumbers?->pluck('phone_number');
            if (!empty($assignedPhoneNumbers)) {
                $phoneNumberArray = array_merge($phoneNumberArray, $assignedPhoneNumbers->toArray());
            }

            $assignedTeamIds = $invitation->assignedInvitationTeam?->pluck('id');
            if (!empty($assignedTeamIds)) {
                $teamPhoneNumbers = $invitation->assignedNumbersForTeam($invitation->id, $assignedTeamIds);
                if (!empty($teamPhoneNumbers)) {
                    $phoneNumberArray = array_merge($phoneNumberArray, $teamPhoneNumbers);
                }
            }
        }

        return array_unique($phoneNumberArray);
    }

}
