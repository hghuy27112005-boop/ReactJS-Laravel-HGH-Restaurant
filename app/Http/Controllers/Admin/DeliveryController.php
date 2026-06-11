<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Get all deliveries (admin view)
     */
    public function index(Request $request)
    {
        $query = Delivery::with('user', 'bill');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('delivery_code', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%')
                    ->orWhereHas('user', function ($subq) use ($request) {
                        $subq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $deliveries = $query->orderByDesc('created_at')
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
     * Update delivery status
     */
    public function updateStatus(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,in_delivery,delivered,cancelled',
        ]);

        $delivery->update(['status' => $validated['status']]);

        // Update related timestamps
        switch ($validated['status']) {
            case 'approved':
                $delivery->update(['approved_at' => now()]);
                break;
            case 'in_delivery':
                $delivery->update(['delivery_started_at' => now()]);
                break;
            case 'delivered':
                $delivery->update(['delivered_at' => now()]);
                break;
        }

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery status updated successfully',
        ]);
    }
}
