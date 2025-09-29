<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function assignRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);
        $user = User::findOrFail($userId);
        $user->assignRole($request->role);
        return response()->json(['message' => 'Role assigned']);
    }

    public function removeRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);
        $user = User::findOrFail($userId);
        $user->removeRole($request->role);
        return response()->json(['message' => 'Role removed']);
    }

    public function givePermission(Request $request, $userId)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);
        $user = User::findOrFail($userId);
        $user->givePermissionTo($request->permission);
        return response()->json(['message' => 'Permission granted']);
    }

    public function revokePermission(Request $request, $userId)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);
        $user = User::findOrFail($userId);
        $user->revokePermissionTo($request->permission);
        return response()->json(['message' => 'Permission revoked']);
    }
}
