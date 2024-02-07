<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvitationLink;
use App\Models\Invitation;
use App\Models\AssignNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->select('id', 'user_id', 'firstname', 'lastname', 'email', 'role', 'number', 'can_have_new_number')
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
}
