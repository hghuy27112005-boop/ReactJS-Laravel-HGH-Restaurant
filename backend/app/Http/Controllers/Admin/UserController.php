<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get all users
     */
    public function index(Request $request)
    {
        $query = User::where('authority', 'User');

        // Filter by membership
        if ($request->has('membership')) {
            $query->where('membership', $request->membership);
        }

        // Search by name or email
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->with('statistics')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Get user details
     */
    public function show(User $user)
    {
        if ($user->authority !== 'User') {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'data' => $user->load('statistics', 'bills', 'deliveries', 'points'),
        ]);
    }

    /**
     * Update user (admin)
     */
    public function update(Request $request, User $user)
    {
        if ($user->authority !== 'User') {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => ['email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'string|max:20',
            'membership' => 'in:Bronze,Silver,Gold,Platinum,Diamond',
            'authority' => 'in:User,Admin',
        ]);

        $user->update($validated);

        // Update statistics if membership changed
        if (isset($validated['membership']) && $user->statistics) {
            $user->statistics->update(['membership' => $validated['membership']]);
        }

        return response()->json([
            'data' => $user,
            'message' => 'User updated successfully',
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        if ($user->authority !== 'User') {
            return response()->json(['message' => 'Cannot delete admin user'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get top customers
     */
    public function topCustomers(Request $request)
    {
        $limit = $request->get('limit', 10);

        $users = User::where('authority', 'User')
            ->with('statistics')
            ->orderByDesc('points_accumulated')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'membership' => $user->membership,
                    'points' => $user->points_accumulated,
                    'total_spent' => $user->statistics?->total_spent ?? 0,
                    'total_orders' => $user->statistics?->total_orders ?? 0,
                ];
            });

        return response()->json([
            'data' => $users,
        ]);
    }

    /**
     * Get customer statistics
     */
    public function statistics(Request $request)
    {
        $totalUsers = User::where('authority', 'User')->count();

        $membershipDistribution = User::where('authority', 'User')
            ->select('membership')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('membership')
            ->get();

        $averageSpent = User::where('authority', 'User')
            ->join('statistics', 'users.id', '=', 'statistics.user_id')
            ->avg('statistics.total_spent');

        $averagePoints = User::where('authority', 'User')
            ->avg('points_accumulated');

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'membership_distribution' => $membershipDistribution,
                'average_spent' => $averageSpent,
                'average_points' => $averagePoints,
            ],
        ]);
    }
}
