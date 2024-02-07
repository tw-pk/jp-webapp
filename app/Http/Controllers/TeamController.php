<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\UserNumber;
use App\Models\Invitation;
use App\Models\UserProfile;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'teamName' => 'required|string',
            'description' => 'required|string',
            'members' => 'required|array|min:1',
        ]);

        $team = Team::find($request->id);
        if (!$team) {
            $team = new Team();
        }
        $team->user_id = Auth::user()->id;
        $team->name = $request->teamName;
        $team->description = $request->description;
        $team->status = 1;
        $team->save();
        if (!empty($request['members']) && is_array($request['members'])) {
            
            $team->members()->sync($request['members']);
        }
        return response()->json([
            'message' => 'Team has been added Successfully!'
        ], 200);
    }

    public function list(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        $query = Team::with([
            'members' => function ($query) {
                $query->select('invitations.id', 'user_id', 'firstname', 'lastname', 'email');
            },
            'assignedNumbers' => function ($query) {
                $query->select('team_id', 'phone_number');
            }
        ])
            ->select('teams.id', 'name', 'description', 'status')
            ->where('user_id', Auth::user()->id);

        if ($searchQuery) {
            $query->where('name', 'like', '%' . $searchQuery . '%');
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
        $teams = $query->paginate($perPage, ['*'], 'page', $currentPage);
        $totalPage = ceil($totalUsers / $perPage);

        $teams->map(function ($team) {
            $members = $team->members ?? null;
            foreach ($members as $member) {
                $member->full_name = $member->firstname . ' ' . $member->lastname;
                if (!empty($member->invitationAccept->profile->avatar)) {
                    $member->avatar_url = asset('storage/avatars/' .$member->invitationAccept->profile->avatar);
                }
            }
            $team['phone_number'] = $team?->assignedNumbers?->pluck('phone_number')->implode(', ');
            return $team;
        });
        return response()->json([
            'teams' => $teams,
            'totalPage' => $totalPage,
            'totalUsers' => $totalUsers,
            'page' => $currentPage,
        ]);
    }

    public function ownedActiveNumbers(){
        $numbers = Auth::user()->numbers;
        $numbers = $numbers->map(function ($number){
           return [
               'number' => $number->phone_number,
               'active' => $number->active
           ];
        });

        return response()->json($numbers);
    }

    public function fetch_numbers()
    {
        $userNumber = UserNumber::select('phone_number')->where('user_id', Auth::user()->id)->get();
        return response()->json([
            'message' => 'Phone numbers fetched successfully',
            'userNumber' => $userNumber
        ]);
    }

    public function fetch_teams()
    {
        $teams = Team::select('id', 'name')->get();
        return response()->json([
            'message' => 'Teams fetched successfully',
            'teams' => $teams
        ]);
    }

    public function fetch_roles()
    {
        $roles = Role::select('id', 'name')->get();
        return response()->json([
            'message' => 'Roles fetched successfully',
            'roles' => $roles
        ]);
    }

    public function fetch_members()
    {
        $inviteMembers = Invitation::with(['invitationAccept' => function ($query) {
            $query->select('id', 'email');
        }, 'invitationAccept.profile' => function ($query) {
            $query->select('user_id', 'avatar');
        }])
            ->select('id', 'user_id', 'firstname', 'lastname', 'email')
            ->where('user_id', Auth::user()->id)
            ->orderBy('id', 'desc')
            ->get();

        $inviteMembers->map(function ($item) {
            $item->fullname = $item->firstname . ' ' . $item->lastname;
            if (!empty($item->invitationAccept->profile->avatar)) {
                $item->avatar_url = asset('storage/avatars/' .$item->invitationAccept->profile->avatar);
            } else {
                $item->avatar_url = asset('images/avatars/avatar-0.png');
            }
            return $item;
        });
        return response()->json([
            'message' => 'Members fetched successfully',
            'inviteMembers' => $inviteMembers,
        ]);
    }

    public function delete_team($id)
    {
        $teamId = $id;
        try {
            $team = Team::find($teamId);
            if ($team) {
                $team->delete();
                return response()->json(['message' => 'Team deleted successfully']);
            } else {
                return response()->json(['message' => 'Team not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete team', 'error' => $e->getMessage()], 500);
        }
    }
}
