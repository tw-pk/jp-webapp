<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNumber;
use App\Models\Invitation;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\Contact;
use App\Models\AssignNumber;
use Illuminate\Support\Facades\Auth;

class PhoneController extends Controller
{

    public function list(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        $perPage = 10;
        $currentPage = $options['page'] ?? 1;

        if (!$searchQuery) {
            $query = Auth::user()->numbers()->select(['user_id', 'phone_number', 'country']);
        } else {
            $query = Auth::user()->numbers()
                ->where(function ($q) use ($searchQuery) {
                    $q->where('phone_number', 'like', '%' . $searchQuery . '%')
                        ->orWhere('country', 'like', '%' . $searchQuery . '%')
                        ->orWhereHas('user', function ($query) use ($searchQuery) {
                            $query->whereRaw("CONCAT(firstname, ' ', lastname) like ?", ['%' . $searchQuery . '%'])
                                ->orWhere('firstname', 'like', '%' . $searchQuery . '%')
                                ->orWhere('lastname', 'like', '%' . $searchQuery . '%');
                        });
                })
                ->select(['user_id', 'phone_number', 'country']);
        }

        $totalRecord = $query->count();
        $numbers = $query->paginate($perPage, ['*'], 'page', $currentPage);
        $totalPage = ceil($totalRecord / $perPage);

        if (!empty($options['sortBy']) && is_array($options['sortBy'])) {
            foreach ($options['sortBy'] as $sort) {
                $key = $sort['key'];
                $order = strtolower($sort['order']) === 'asc' ? 'asc' : 'desc';
                $query->orderBy($key, $order);
            }
        }
        
        $numbers->map(function ($userNumber) {
            $userNumber->makeHidden(['user']);
            $invitations = $userNumber->user->userInvitations;
            
            $shareWith = [];
            foreach ($invitations as $key => $invitation) {
               
                $user = User::where('email', $invitation->email)
                    ->select(['id', 'email', 'firstname', 'lastname'])
                    ->first();

                if (!empty($user->profile?->avatar)) {
                    $shareWith[$key]['avatar_url'] = asset('storage/avatars/' .$user->profile->avatar);
                }

                $shareWith[$key]['firstname'] = $user?->firstname ? $user->firstname : $invitation->firstname;
                $shareWith[$key]['lastname'] = $user?->lastname ? $user?->lastname : $invitation->lastname;
            }

            $userNumber->shared = $shareWith;
            $userNumber['ownerFullName'] = $userNumber->user->fullName();

            return $userNumber;
        });
        return response()->json([
            'numbers' => $numbers,
            'totalPage' => $totalPage,
            'totalRecord' => $totalRecord,
            'page' => $currentPage,
        ]);
    }

    public function phone_assign(Request $request)
    {
        $request->validate([
            'number' => 'required',
            'member' => 'nullable|integer|required_without_all:team',
            'team' => 'nullable|integer|required_without_all:member',
        ]);

        $phone_number = $request->number;
        $team_id = $request->team ?? '';
        $invitation_id = $request->member ?? '';

        $invitationAssignment = '';
        $teamAssignment = '';

        $invitationAssignment = AssignNumber::where(['phone_number' => $phone_number, 'invitation_id' => $invitation_id])->first();
        if (empty($invitationAssignment) && !empty($invitation_id) && !empty($phone_number)) {
            AssignNumber::create([
                'invitation_id' => $invitation_id,
                'phone_number' => $phone_number,
            ]);
        }

        $teamAssignment = AssignNumber::where(['phone_number' => $phone_number, 'team_id' => $team_id])->first();
        if (empty($teamAssignment) && !empty($team_id) && !empty($phone_number)) {
            AssignNumber::create([
                'team_id' => $team_id,
                'phone_number' => $phone_number,
            ]);
        }
        return response()->json([
            'message' => 'The Phone Number has been successfully assigned.'
        ]);
    }

    public function dialer_contacts(Request $request)
    {
        $searchQuery = $request->input('q');
        $contacts = Contact::with(['userProfile' => function ($query) {
            $query->select('contact_id', 'avatar');
        }])
        ->select('id', 'user_id', 'firstname', 'lastname', 'email', 'phone')
        ->where('user_id', Auth::user()->id)
        ->when($searchQuery, function($query) use ($searchQuery) {
            $query->where(function ($subQuery) use ($searchQuery) {
                $subQuery->where('firstname', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('lastname', 'LIKE', "%{$searchQuery}%")
                        ->orwhere('email', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('phone', 'LIKE', "%{$searchQuery}%");
            });
        })
        ->get();

        $final_contacts = [];
        foreach ($contacts as $key => $contact) {
            $final_contacts[$key]['fullName'] = $contact->firstname . ' ' . $contact->lastname;
            $final_contacts[$key]['email'] = $contact->email;
            $final_contacts[$key]['phone'] = $contact->phone;
            if (!empty($contact->userProfile->avatar)) {
                $final_contacts[$key]['avatar_url'] = asset('storage/' . $contact->userProfile->avatar);
            } else {
                $final_contacts[$key]['avatar_url'] = asset('images/avatars/avatar-0.png');
            }
        }
        //some code is remaing assign number.. 
        return response()->json([
            'contacts' => $final_contacts,
        ]);
    }
}
