<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * Get all users with filters
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->orderBy('created_at', 'desc')->get()->map(function($user) {
            // Get user stats
            $orderCount = DB::table('orders')->where('user_id', $user->id)->count();
            $totalSpent = DB::table('orders')->where('user_id', $user->id)->sum('total_amount');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'buyer',
                'status' => $user->status ?? 'active',
                'avatar' => $user->avatar,
                'created_at' => $user->created_at,
                'total_orders' => $orderCount,
                'total_spent' => $totalSpent ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,seller,buyer',
        ]);

        $user = User::findOrFail($id);
        $user->role = $request->input('role');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,suspended,banned',
        ]);

        $user = User::findOrFail($id);
        $user->status = $request->input('status');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $user->status,
            ],
        ]);
    }
}
