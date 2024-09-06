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
use App\Services\AssignPhoneNumberService;

class PhoneController extends Controller
{

    public function list(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        $perPage = $options['itemsPerPage'] ?? 10;
        $currentPage = $options['page'] ?? 1;

        $userId = Auth::user()->id;
        $assignPhoneNumberService = new AssignPhoneNumberService();
        $numbers = $assignPhoneNumberService->getAssignPhoneNumbers($userId);
    
        $userNumbers = UserNumber::with('user.userInvitations')
            ->select('user_id', 'phone_number', 'country')
            ->whereIn('phone_number', $numbers);  

        if ($searchQuery) {
            $userNumbers->where(function ($q) use ($searchQuery) {
                $q->where('phone_number', 'like', '%' . $searchQuery . '%')
                    ->orWhere('country', 'like', '%' . $searchQuery . '%')
                    ->orWhereHas('user', function ($query) use ($searchQuery) {
                        $query->whereRaw("CONCAT(firstname, ' ', lastname) like ?", ['%' . $searchQuery . '%'])
                            ->orWhere('firstname', 'like', '%' . $searchQuery . '%')
                            ->orWhere('lastname', 'like', '%' . $searchQuery . '%');
                    });
            });
        }

        $totalRecord = $userNumbers->count();
        if (!empty($options['sortBy']) && is_array($options['sortBy'])) {
            foreach ($options['sortBy'] as $sort) {
                $key = $sort['key'];
                $order = strtolower($sort['order']) === 'asc' ? 'asc' : 'desc';
                $userNumbers->orderBy($key, $order);
            }
        }
        
        $numbersPaginated = $userNumbers->paginate($perPage, ['*'], 'page', $currentPage);
        $totalPage = ceil($totalRecord / $perPage);

        $numbersPaginated->map(function ($userNumber) {
            $isShared = 'shared';
            if ($userNumber->user_id == Auth::user()->id) {
                $isShared = 'personal';
            }
            $userNumber['isShared'] = $isShared;
        
            $userNumber->makeHidden(['user']);
            $invitations = $userNumber->user->userInvitations;
            
            $shareWith = [];
            foreach ($invitations as $key => $invitation) {
                $user = User::where('id', $invitation->member_id)
                    ->select(['id', 'email', 'firstname', 'lastname'])
                    ->first();
                if (!empty($user->profile?->avatar)) {
                    $shareWith[$key]['avatar_url'] = asset('storage/avatars/' . $user->profile->avatar);
                }
                $shareWith[$key]['firstname'] = $user?->firstname ? $user->firstname : $invitation->firstname;
                $shareWith[$key]['lastname'] = $user?->lastname ? $user?->lastname : $invitation->lastname;
            }

            $userNumber->shared = $shareWith;
            $userNumber['ownerFullName'] = $userNumber->user->fullName();
            return $userNumber;
        });
        
        return response()->json([
            'numbers' => $numbersPaginated,
            'totalPage' => $totalPage,
            'totalRecord' => $totalRecord,
            'page' => $currentPage,
        ]);
    }

    public function fetchAssignNumber(Request $request)
    {
        if (Auth::user()->numbers()->where('phone_number', $request->number)->exists()) {

            $assignNumber = AssignNumber::select('team_id', 'invitation_id')->where('phone_number', $request->number)->get();

            return response()->json([
                'status' => true,
                'message' => 'Assign phone fetched successfully',
                'assigned' => $assignNumber
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Not authorized'
            ]);
        }
    }

    public function phone_assign(Request $request)
    {
        $request->validate([
            'number' => 'required',
            'member' => 'nullable|array|required_without_all:team',
            'team' => 'nullable|array|required_without_all:member',
        ]);

        $phone_number = $request->number;
        $invitation_ids = is_array($request->member) ? $request->member : [$request->member];
        $team_ids = is_array($request->team) ? $request->team : [$request->team];
       
        if (!empty($invitation_ids) && is_array($invitation_ids)) {
            foreach ($invitation_ids as $invitation_id) {
                $assignment = AssignNumber::updateOrCreate(
                    ['invitation_id' => $invitation_id],
                    ['phone_number' => $phone_number]
                );
            }
            AssignNumber::where('phone_number', $phone_number)
            ->where(function ($query) use ($invitation_ids) {
                $query->whereNotIn('invitation_id', $invitation_ids)
                        ->orWhereNotNull('team_id'); 
            })
            ->delete();

        }

        if (!empty($team_ids) && is_array($team_ids)) {
            foreach ($team_ids as $team_id) {
                $assignment = AssignNumber::updateOrCreate(
                    ['team_id' => $team_id],
                    ['phone_number' => $phone_number]
                );
            }
            AssignNumber::where('phone_number', $phone_number)
                ->where(function ($query) use ($team_ids) {
                $query->whereNotIn('team_id', $team_ids)
                        ->orWhereNotNull('invitation_id'); 
            })
            ->delete();
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
