<?php

namespace App\Http\Controllers;

use App\Models\BookingTable;
use App\Models\Bill;
use Illuminate\Http\Request;

class BookingTableController extends Controller
{
    /**
     * Get all bookings for user
     */
    public function index(Request $request)
    {
        $bookings = $request->user()->bills()
            ->where('order_type', 'booking_table')
            ->with('bookingTable', 'orders.dish')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $bookings->items(),
            'pagination' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * Create booking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'table_number' => 'required|integer|between:1,50',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'guests_count' => 'required|integer|min:1|max:20',
        ]);

        $bill = Bill::findOrFail($validated['bill_id']);
        
        if ($bill->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if table is available
        if (!$this->isTableAvailable(
            $validated['table_number'],
            $validated['start_time'],
            $validated['end_time']
        )) {
            return response()->json([
                'message' => 'Table is not available for the selected time',
            ], 422);
        }

        $booking = BookingTable::create([
            'bill_id' => $validated['bill_id'],
            'table_number' => $validated['table_number'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'guests_count' => $validated['guests_count'],
        ]);

        return response()->json([
            'data' => $booking,
            'message' => 'Table booked successfully',
        ], 201);
    }

    /**
     * Get booking details
     */
    public function show(BookingTable $bookingTable)
    {
        if ($bookingTable->bill->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $bookingTable->load('bill'),
        ]);
    }

    /**
     * Update booking
     */
    public function update(Request $request, BookingTable $bookingTable)
    {
        if ($bookingTable->bill->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'table_number' => 'integer|between:1,50',
            'start_time' => 'date|after:now',
            'end_time' => 'date|after:start_time',
            'guests_count' => 'integer|min:1|max:20',
        ]);

        // Check if new table is available
        if (isset($validated['table_number']) || isset($validated['start_time'])) {
            $table = $validated['table_number'] ?? $bookingTable->table_number;
            $start = $validated['start_time'] ?? $bookingTable->start_time;
            $end = $validated['end_time'] ?? $bookingTable->end_time;

            if (!$this->isTableAvailable($table, $start, $end, $bookingTable->id)) {
                return response()->json([
                    'message' => 'Table is not available for the selected time',
                ], 422);
            }
        }

        $bookingTable->update($validated);

        return response()->json([
            'data' => $bookingTable,
            'message' => 'Booking updated successfully',
        ]);
    }

    /**
     * Cancel booking
     */
    public function destroy(BookingTable $bookingTable)
    {
        if ($bookingTable->bill->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if booking is not in the past
        if ($bookingTable->start_time < now()) {
            return response()->json([
                'message' => 'Cannot cancel past bookings',
            ], 422);
        }

        $bookingTable->delete();

        return response()->json([
            'message' => 'Booking cancelled successfully',
        ]);
    }

    /**
     * Get available tables for date/time
     */
    public function getAvailable(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|after:today',
            'time' => 'required|date_format:H:i',
            'duration_hours' => 'integer|min:1|max:5',
        ]);

        $durationHours = $validated['duration_hours'] ?? 2;
        $startTime = $validated['date'] . ' ' . $validated['time'];
        $endTime = \Carbon\Carbon::parse($startTime)->addHours($durationHours);

        $bookedTables = BookingTable::where(function ($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($subq) use ($startTime, $endTime) {
                    $subq->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                });
        })->pluck('table_number')->toArray();

        $availableTables = [];
        for ($i = 1; $i <= 50; $i++) {
            if (!in_array($i, $bookedTables)) {
                $availableTables[] = [
                    'table_number' => $i,
                    'capacity' => $i <= 2 ? 2 : ($i <= 4 ? 4 : ($i <= 8 ? 8 : 12)),
                    'available_slots' => [
                        [
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ],
                    ],
                ];
            }
        }

        return response()->json([
            'data' => $availableTables,
        ]);
    }

    /**
     * Check if table is available
     */
    private function isTableAvailable($tableNumber, $startTime, $endTime, $excludeBookingId = null)
    {
        $query = BookingTable::where('table_number', $tableNumber)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($subq) use ($startTime, $endTime) {
                        $subq->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->count() === 0;
    }
}
