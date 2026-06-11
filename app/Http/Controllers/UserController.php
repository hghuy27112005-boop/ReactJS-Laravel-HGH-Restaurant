<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load('statistics', 'discounts');

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => ['email', Rule::unique('users')->ignore($request->user()->id)],
            'phone' => 'string|max:20',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'data' => $request->user(),
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->file('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $request->user()->update([
                'avatar_url' => '/storage/' . $path,
            ]);
        }

        return response()->json([
            'data' => [
                'avatar_url' => $request->user()->avatar_url,
            ],
            'message' => 'Avatar uploaded successfully',
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!password_verify($validated['current_password'], $request->user()->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $request->user()->update([
            'password' => bcrypt($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!password_verify($validated['password'], $request->user()->password)) {
            return response()->json([
                'message' => 'Password is incorrect',
            ], 422);
        }

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }

    /**
     * Get user orders
     */
    public function orders(Request $request)
    {
        $orders = $request->user()->bills()
            ->with('orders.dish', 'delivery', 'bookingTable')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Get user discounts
     */
    public function discounts(Request $request)
    {
        $discounts = $request->user()->discounts()
            ->where('is_active', true)
            ->get();

        return response()->json([
            'data' => $discounts,
        ]);
    }

    /**
     * Get user deliveries
     */
    public function deliveries(Request $request)
    {
        $deliveries = $request->user()->deliveries()
            ->with('bill', 'points')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $deliveries->items(),
            'pagination' => [
                'total' => $deliveries->total(),
                'per_page' => $deliveries->perPage(),
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
            ],
        ]);
    }
}
