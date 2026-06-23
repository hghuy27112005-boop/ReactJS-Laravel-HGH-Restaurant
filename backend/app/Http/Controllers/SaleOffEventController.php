<?php

namespace App\Http\Controllers;

use App\Models\SaleOffEvent;
use Illuminate\Http\Request;

class SaleOffEventController extends Controller
{
    /**
     * Danh sách sale off events (public).
     */
    public function index(Request $request)
    {
        $query = SaleOffEvent::query();

        if ($request->boolean('active_only')) {
            $now = now();
            $query->where('start_time', '<=', $now)
                  ->where('end_time', '>=', $now);
        }

        return response()->json([
            'data' => $query->orderByDesc('start_time')->get(),
        ]);
    }

    /**
     * Chi tiết 1 sale off event (public).
     */
    public function show(SaleOffEvent $saleOffEvent)
    {
        return response()->json([
            'data' => $saleOffEvent,
        ]);
    }

    /**
     * Tạo sale off event mới (admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_off_percentage' => 'required|numeric|min:0|max:100',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $event = SaleOffEvent::create($validated);

        return response()->json([
            'data' => $event,
            'message' => 'Sale off event created successfully',
        ], 201);
    }

    /**
     * Cập nhật sale off event (admin).
     */
    public function update(Request $request, SaleOffEvent $saleOffEvent)
    {
        $validated = $request->validate([
            'sale_off_percentage' => 'sometimes|numeric|min:0|max:100',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
        ]);

        $saleOffEvent->update($validated);

        return response()->json([
            'data' => $saleOffEvent,
            'message' => 'Sale off event updated successfully',
        ]);
    }

    /**
     * Xoá sale off event (admin).
     */
    public function destroy(SaleOffEvent $saleOffEvent)
    {
        $saleOffEvent->delete();

        return response()->json([
            'message' => 'Sale off event deleted successfully',
        ]);
    }
}
