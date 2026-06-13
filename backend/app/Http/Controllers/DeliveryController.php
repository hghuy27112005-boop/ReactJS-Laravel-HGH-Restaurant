<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Get all deliveries for user
     */
    public function index(Request $request)
    {
        $query = $request->user()->deliveries();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->with('user', 'bill', 'points')
            ->paginate($request->get('per_page', 20));

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

    /**
     * Create delivery request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $bill = Bill::findOrFail($validated['bill_id']);
        $this->authorize('view', $bill);

        if ($bill->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deliveryCode = $this->generateDeliveryCode();

        $delivery = Delivery::create([
            'user_id' => $request->user()->id,
            'bill_id' => $validated['bill_id'],
            'delivery_code' => $deliveryCode,
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'status' => 'pending',
            'final_price' => $bill->total_amount + 5000, // Add shipping fee
        ]);

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery request created successfully',
        ], 201);
    }

    /**
     * Get delivery details
     */
    public function show(Delivery $delivery)
    {
        if ($delivery->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $delivery->load('user', 'bill', 'points'),
        ]);
    }

    /**
     * Update delivery (for admin)
     */
    public function update(Request $request, Delivery $delivery)
    {
        $this->authorize('update', $delivery);

        $validated = $request->validate([
            'status' => 'in:pending,approved,in_delivery,delivered,cancelled',
            'address' => 'string|max:255',
            'phone' => 'string|max:20',
        ]);

        $delivery->update($validated);

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery updated successfully',
        ]);
    }

    /**
     * Approve delivery
     */
    public function approve(Request $request, Delivery $delivery)
    {
        if ($delivery->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $delivery->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery approved',
        ]);
    }

    /**
     * Start delivery
     */
    public function startDelivery(Request $request, Delivery $delivery)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Only admins can start deliveries'], 403);
        }

        if ($delivery->status !== 'approved') {
            return response()->json([
                'message' => 'Delivery must be approved before starting',
            ], 422);
        }

        $delivery->update([
            'status' => 'in_delivery',
            'delivery_started_at' => now(),
        ]);

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery started',
        ]);
    }

    /**
     * Complete delivery
     */
    public function completeDelivery(Request $request, Delivery $delivery)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Only admins can complete deliveries'], 403);
        }

        if ($delivery->status !== 'in_delivery') {
            return response()->json([
                'message' => 'Delivery must be in progress',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $delivery->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

            // Update bill status
            $delivery->bill->update(['status' => 'completed']);

            DB::commit();

            return response()->json([
                'data' => $delivery,
                'message' => 'Delivery completed',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate unique delivery code
     */
    private function generateDeliveryCode()
    {
        $date = date('dmy');
        $sequence = Delivery::whereDate('created_at', today())->count() + 1;
        return $date . 'D' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
