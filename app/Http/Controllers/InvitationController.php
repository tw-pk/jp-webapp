<?php

namespace App\Http\Controllers;

use App\Jobs\SendInvitationLink;
use App\Jobs\SendInvitationsToUsers;
use App\Models\Invitation;
use App\Models\Member;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class InvitationController extends Controller
{


    public function store(Request $request)
    {
        $request->validate([
            'members' => 'required'
        ]);

        $members = $request->members;

        foreach ($members as $member) {

            $emailValidator = new EmailValidator();
            $isValid = $emailValidator->isValid($member['emailAddress'], new RFCValidation());

            if (!$isValid) {
                return response()->json([
                    'status' => false,
                    'message' => $member->email . " is not a valid email address!"
                ]);
            }

            $invitation = Invitation::create([
                'user_id' => Auth::user()->id,
                'firstname' => $member['firstName'],
                'lastname' => $member['lastName'],
                'email' => $member['emailAddress'],
                'role' => Role::where('name', 'Member')->first()?->id,
                'registered' => false,
                'number' => $member['existingNumber'],
                'can_have_new_number' => $member['allowNewNumber']
            ]);

            dispatch(new SendInvitationLink($invitation));
        }

        return response()->json([
            'status' => true,
            'message' => 'Invitation(s) sent to ' . count($members) . ' member(s)'
        ]);
    }
}
