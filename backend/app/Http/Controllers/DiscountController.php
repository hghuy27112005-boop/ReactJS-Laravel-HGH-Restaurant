<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    /**
     * Danh sách discount đang active của user hiện tại.
     */
    public function userDiscounts(Request $request)
    {
        $user = $request->user();

        $discounts = Discount::where('user_id', $user->user_id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'data' => $discounts,
        ]);
    }

    /**
     * Discount áp dụng theo cấp membership cụ thể.
     */
    public function byMembership(string $membership)
    {
        $discounts = Discount::where('membership', $membership)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'data' => $discounts,
        ]);
    }
}
