<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvitationLink;
use App\Models\Invitation;
use App\Models\AssignNumber;
use App\Models\User;
use App\Models\UserNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MembersController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'firstName' => 'required|string',
            'email' => 'required|email|unique:invitations,email,' . ($request->all() ? $request->id : 'NULL') . ',id',
            'role' => 'required|integer',
            'assignNumber' => 'required|string',
            'number' => $request->assignNumber =='Existing Number' ? 'required' : '',
        ]);
        
        $invitation = Invitation::find($request->id);
        if (!$invitation) {
            $invitation = new Invitation();
            $invitation->registered = false;
        }
        $invitation->user_id = Auth::user()->id;
        $invitation->firstname = $request->firstName;
        $invitation->lastname = $request->lastName;
        $invitation->email = $request->email;
        $invitation->role = $request->role;
        $invitation->number = $request->number;
        $invitation->can_have_new_number = $request->assignNumber =='Existing Number' ? 0:1;
        $invitation->save();

        AssignNumber::updateOrCreate(
            ['invitation_id' => $invitation->id],
            ['phone_number' => $request->number]
        );

        dispatch(new SendInvitationLink($invitation));

        return response()->json([
            'message' => 'An invitation link has been sent to member email'
        ]);
    }

    public function verifyToken(Request $request)
    {
        $request->validate([
            'invitationToken' => 'required',
            'email' => 'required'
        ]);

        $invitationToken = decrypt($request->invitationToken);
        $invitation_email = $request->email;
        $invitation = Invitation::where('id', $invitationToken)->where('email', $invitation_email)->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invalid invitation link'
            ], 404);
        }

        return response()->json([
            'firstname' => $invitation->firstname,
            'lastname' => $invitation->lastname,
            'email' => $invitation->email
        ]);
    }

    public function list(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        $query = Invitation::with(['invitationAccept:id,email', 'invitationAccept.profile:id,user_id,avatar', 'roleInfo:id,name'])
            ->select('id', 'user_id', 'firstname', 'lastname', 'email', 'role', 'number', 'can_have_new_number', 'registered')
            ->where('user_id', Auth::user()->id);

        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('firstname', 'like', '%' . $searchQuery . '%')
                    ->orWhere('lastname', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        if (!empty($options['sortBy']) && is_array($options['sortBy'])) {
            foreach ($options['sortBy'] as $sort) {
                $key = $sort['key'];
                $order = strtolower($sort['order']) === 'asc' ? 'asc' : 'desc';
                $query->orderBy($key, $order);
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $totalUsers = $query->count();
        $perPage = 5;
        $currentPage = $options['page'] ?? 1;
        $members = $query->paginate($perPage, ['*'], 'page', $currentPage);
        $totalPage = ceil($totalUsers / $perPage);
        
        return response()->json([
            'members' => $members,
            'totalPage' => $totalPage,
            'totalUsers' => $totalUsers,
            'page' => $currentPage,
        ]);
    }

    public function fetchMembers()
    {
        try {
            $userId = Auth::user()->id;
            $members = User::whereHas('invitationsMember', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereHas('invitationsMember', function ($query) {
                $query->whereColumn('member_id', 'users.id');
            })
            ->select(['id',  \DB::raw("CONCAT(firstname, ' ', lastname) AS fullname")])
            ->get();
            
            if(!$members){
                return response()->json([
                    'status' => false,
                    'message' => 'Member not found'
                ]);
            }
            return response()->json([
                'status' => true,
                'members' => $members
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchMembersForChart()
    {
        try {
            $userId = Auth::user()->id;
            $members = User::whereHas('invitationsMember', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->whereHas('invitationsMember', function ($query) {
                    $query->whereColumn('member_id', 'users.id');
                })
                ->with(['profile' => function($query) {
                    $query->select('user_id', 'avatar'); 
                }])
                ->select(['id', 'firstname', 'lastname'])
                ->get();
            
            $members->map(function ($item) {
                $item->fullname = $item->firstname . ' ' . $item->lastname;

                if (!empty($item->profile->avatar)) {
                    $item->avatar_url = asset('storage/avatars/' . $item->profile->avatar);
                } else {
                    $item->avatar_url = asset('images/avatars/avatar-0.png');
                }
                return $item;
            });

            if(!$members){
                return response()->json([
                    'status' => false,
                    'message' => 'Member not found'
                ]);
            }
            return response()->json([
                'status' => true,
                'members' => $members->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchMemberDetail(Request $request)
    { 
        try {
            $member = Invitation::find($request->member_id);
            if(!$member){
                return response()->json([
                    'status' => false,
                    'message' => 'Member not found'
                ]);
            }
            
            $memberDetail = [
                'fullName' => $member->fullName(),
                'email' => $member->email,
                'number' => $member->number,
                'registered' => $member->registered ==1 ? "True" : "False",
                'role' => $member->roleInfo ? $member->roleInfo->name : 'Role not found',
                'invitationDate' => Carbon::parse($member->created_at)->format('d M, Y'),
                'avatar' => $member->invitationAccept && $member->invitationAccept->profile && $member->invitationAccept->profile->avatar
                ? asset('storage/avatars/' . $member->invitationAccept->profile->avatar)
                : null,
            ];
            return response()->json([
                'status' => true,
                'memberDetail' => $memberDetail
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteMember($id)
    {
        $memberId = $id;
        try {
            $invitation = Invitation::find($id);
            if (!$invitation) {
                return response()->json([
                    'status' => false,
                    'message' => 'Member not found'
                ], 404);
            }

            if ($invitation->member_id) {
                $user = User::find($invitation->member_id);
                if ($user) {
                    $user->numbers()->delete();
                    $user->delete();
                }
            }
            $invitation->delete();
            return response()->json([
                'status' => true,
                'message' => 'Member deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
