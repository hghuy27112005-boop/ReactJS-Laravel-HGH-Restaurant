<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Dish;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * Get all stocks
     */
    public function index(Request $request)
    {
        $query = Stock::with('dish');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by low stock
        if ($request->has('low_stock') && $request->low_stock == 'true') {
            $query->where('quantity_left', '<=', 15);
        }

        $stocks = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $stocks->items(),
            'pagination' => [
                'total' => $stocks->total(),
                'per_page' => $stocks->perPage(),
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
            ],
        ]);
    }

    /**
     * Create stock
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dish_id' => 'required|exists:dishes,dish_id',
            'quantity_start' => 'required|integer|min:1',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        $stock = Stock::create([
            'dish_id' => $validated['dish_id'],
            'quantity_start' => $validated['quantity_start'],
            'quantity_left' => $validated['quantity_start'],
            'cost_per_unit' => $validated['cost_per_unit'],
            'status' => 'active',
        ]);

        return response()->json([
            'data' => $stock->load('dish'),
            'message' => 'Stock created successfully',
        ], 201);
    }

    /**
     * Get stock details
     */
    public function show(Stock $stock)
    {
        return response()->json([
            'data' => $stock->load('dish'),
        ]);
    }

    /**
     * Update stock
     */
    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity_left' => 'integer|min:0',
            'status' => 'in:active,low_stock,expired,replaced',
            'cost_per_unit' => 'numeric|min:0',
        ]);

        $stock->update($validated);

        // Check if needs to be marked as low stock
        if ($stock->quantity_left <= 15 && $stock->status === 'active') {
            $stock->update(['status' => 'low_stock']);
        }

        return response()->json([
            'data' => $stock,
            'message' => 'Stock updated successfully',
        ]);
    }

    /**
     * Delete stock
     */
    public function destroy(Stock $stock)
    {
        $stock->delete();

        return response()->json([
            'message' => 'Stock deleted successfully',
        ]);
    }

    /**
     * Get low stock items
     */
    public function lowStock(Request $request)
    {
        $stocks = Stock::where('quantity_left', '<=', 15)
            ->with('dish')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $stocks->items(),
            'pagination' => [
                'total' => $stocks->total(),
                'per_page' => $stocks->perPage(),
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
            ],
        ]);
    }

    /**
     * Decrease stock (when order is placed)
     */
    public function decreaseQuantity(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if ($stock->quantity_left < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock',
            ], 422);
        }

        $stock->decreaseQuantity($validated['quantity']);

        return response()->json([
            'data' => $stock,
            'message' => 'Stock decreased successfully',
        ]);
    }

    /**
     * Increase stock (for restocking)
     */
    public function increaseQuantity(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $stock->increaseQuantity($validated['quantity']);

        // Reset status if restocking from low stock
        if ($stock->status === 'low_stock' && $stock->quantity_left > 15) {
            $stock->update(['status' => 'active']);
        }

        return response()->json([
            'data' => $stock,
            'message' => 'Stock increased successfully',
        ]);
    }

    /**
     * Mark stock as replaced
     */
    public function markReplaced(Request $request, Stock $stock)
    {
        $stock->update([
            'status' => 'replaced',
            'replaced_at' => now(),
        ]);

        return response()->json([
            'data' => $stock,
            'message' => 'Stock marked as replaced',
        ]);
    }

    /**
     * Get stock revenue report
     */
    public function revenueReport(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Stock::with('dish');

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $stocks = $query->get();

        $report = $stocks->map(function ($stock) {
            $revenue = $stock->getRevenueFromDish();
            return [
                'id' => $stock->id,
                'dish' => $stock->dish,
                'quantity_start' => $stock->quantity_start,
                'quantity_left' => $stock->quantity_left,
                'cost_per_unit' => $stock->cost_per_unit,
                'total_cost' => $stock->quantity_start * $stock->cost_per_unit,
                'revenue' => $revenue,
                'profit' => $revenue - ($stock->quantity_start * $stock->cost_per_unit),
            ];
        });

        return response()->json([
            'data' => $report,
            'summary' => [
                'total_cost' => $report->sum('total_cost'),
                'total_revenue' => $report->sum('revenue'),
                'total_profit' => $report->sum('profit'),
            ],
        ]);
    }
}
