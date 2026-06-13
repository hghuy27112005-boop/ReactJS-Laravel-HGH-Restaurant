<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingTable extends Model
{
    protected $table = 'booking_tables';

    protected $fillable = [
        'booking_code',
        'table_number',
        'booking_date',
        'arrival_time',
        'end_time',
        'guest_count',
        'user_id',
        'bill_id',
        'status',
        'note',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'arrival_time' => 'datetime',
        'end_time' => 'datetime',
        'guest_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeUpcoming($query)
    {
        return $query->where('arrival_time', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_time', '<', now());
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Generate booking code (DDMMYY_SEQUENCE)
     */
    public function generateBookingCode()
    {
        $today = now();
        $prefix = $today->format('dmy');
        
        $count = BookingTable::whereDate('created_at', $today)->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        $this->booking_code = $prefix . '_' . $sequence;
        $this->save();
    }

    /**
     * Check if booking is upcoming
     */
    public function isUpcoming()
    {
        return $this->arrival_time > now() && $this->status !== 'cancelled';
    }

    /**
     * Check if booking is completed
     */
    public function isCompleted()
    {
        return $this->end_time < now() && $this->status === 'confirmed';
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes()
    {
        if (!$this->end_time) {
            return null;
        }

        return $this->arrival_time->diffInMinutes($this->end_time);
    }

    /**
     * Get duration in hours
     */
    public function getDurationHours()
    {
        $minutes = $this->getDurationMinutes();
        if (!$minutes) {
            return null;
        }

        return round($minutes / 60, 1);
    }

    /**
     * Get table capacity based on table number
     */
    public function getTableCapacity()
    {
        $tableNo = $this->table_number;
        
        if ($tableNo <= 2) return 2;
        if ($tableNo <= 4) return 4;
        if ($tableNo <= 8) return 8;
        return 12;
    }

    /**
     * Check if enough capacity for guests
     */
    public function hasEnoughCapacity()
    {
        return $this->guest_count <= $this->getTableCapacity();
    }

    /**
     * Cancel booking
     */
    public function cancel()
    {
        $this->status = 'cancelled';
        $this->save();
        
        // Delete associated bill if exists
        if ($this->bill_id) {
            $this->bill()->delete();
        }
    }

    /**
     * Confirm booking
     */
    public function confirm()
    {
        $this->status = 'confirmed';
        $this->save();
    }

    /**
     * Get booking info
     */
    public function getBookingInfo()
    {
        return [
            'booking_code' => $this->booking_code,
            'table' => $this->table_number,
            'date' => $this->booking_date->format('Y-m-d'),
            'time' => $this->arrival_time->format('H:i'),
            'duration' => $this->getDurationHours() . ' hours',
            'guests' => $this->guest_count,
            'status' => $this->status,
        ];
    }
}
