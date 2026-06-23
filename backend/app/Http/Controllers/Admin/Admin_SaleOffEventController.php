<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleOffEvent;
use Illuminate\Http\Request;

class Admin_SaleOffEventController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => SaleOffEvent::orderByDesc('start_time')
                ->paginate($request->get('per_page', 20)),
        ]);
    }

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

    public function show(SaleOffEvent $saleOffEvent)
    {
        return response()->json(['data' => $saleOffEvent]);
    }

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

    public function destroy(SaleOffEvent $saleOffEvent)
    {
        $saleOffEvent->delete();

        return response()->json([
            'message' => 'Sale off event deleted successfully',
        ]);
    }
}
