<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class Admin_DiscountController extends Controller
{
    public function index(Request $request)
    {
        $query = Discount::query();

        if ($request->has('membership')) {
            $query->where('membership', $request->membership);
        }

        return response()->json([
            'data' => $query->paginate($request->get('per_page', 20)),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,user_id',
            'membership' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $discount = Discount::create($validated);

        return response()->json([
            'data' => $discount,
            'message' => 'Discount created successfully',
        ], 201);
    }

    public function show(Discount $discount)
    {
        return response()->json(['data' => $discount]);
    }

    public function update(Request $request, Discount $discount)
    {
        $validated = $request->validate([
            'discount_percentage' => 'sometimes|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $discount->update($validated);

        return response()->json([
            'data' => $discount,
            'message' => 'Discount updated successfully',
        ]);
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();

        return response()->json([
            'message' => 'Discount deleted successfully',
        ]);
    }
}
