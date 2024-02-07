<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomRole;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{

    public function fetch_role(Request $request)
    {
        $searchQuery = $request->input('q');

        $roles = CustomRole::with(['createdBy' => function ($query) {
            $query->select('id', 'firstname', 'lastname');
        }, 'lastUpdatedBy' => function ($query) {
            $query->select('id', 'firstname', 'lastname');
        }])
            ->where(function ($query) use ($searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->orderByDesc('id')
            ->get(['id', 'name', 'guard_name', 'created_by', 'last_updated_by', 'updated_at']);

        $rolesData = $roles->map(function ($role) {
            $lastUpdated = $role->updated_at
                ? $role->updated_at->format('M d, Y, h:i A')
                : 'N/A';

            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'created_by' => $role->createdBy ? "{$role->createdBy->firstname} {$role->createdBy->lastname}" : 'N/A',
                'last_updated' => "{$lastUpdated} By "
                    . ($role->lastUpdatedBy
                        ? "{$role->lastUpdatedBy->firstname} {$role->lastUpdatedBy->lastname}"
                        : 'N/A'),
                'checked' => false,
                'user_count' => DB::table('model_has_roles')->where('role_id', $role->id)->count() ?? 0,
            ];
        });
        return response()->json([
            'roles' => $rolesData,
        ]);
    }

    public function add_role(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
        ]);

        $role = Role::find($request->id);
        if (!$role) {
            $role = new Role();
            $role->created_by = Auth::user()->id;
        }
        $role->name = ucfirst($request->role);
        $role->guard_name = 'api';
        $role->last_updated_by = Auth::user()->id;
        $role->save();

        return response()->json([
            'message' => 'Role has been added Successfully!'
        ], 200);
    }

    public function delete_role($id)
    {
        $roleId = $id;
        try {
            $role = CustomRole::find($roleId);
            if ($role) {
                $role->delete();
                return response()->json(['message' => 'Role has been deleted successfully']);
            } else {
                return response()->json(['message' => 'Role not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete team', 'error' => $e->getMessage()], 500);
        }
    }
}
