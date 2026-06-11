<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $table = 'bills';

    protected $fillable = [
        'bill_code',
        'user_id',
        'order_type',
        'total_amount',
        'status',
        'is_paid',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
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

    public function orders()
    {
        return $this->hasMany(Order::class, 'bill_id', 'id');
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class, 'bill_id', 'id');
    }

    public function bookingTable()
    {
        return $this->hasOne(BookingTable::class, 'bill_id', 'id');
    }

    public function points()
    {
        return $this->hasMany(Points::class, 'bill_id', 'id');
    }

    public function billDetails()
    {
        return $this->hasMany(BillDetail::class, 'bill_id', 'id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaidStatus($query)
    {
        return $query->where('is_paid', false);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if bill is a dine-in order
     */
    public function isBookingTable()
    {
        return $this->order_type === 'booking_table';
    }

    /**
     * Check if bill is a delivery order
     */
    public function isDelivery()
    {
        return $this->order_type === 'delivery';
    }

    /**
     * Check if bill is paid
     */
    public function isPaid()
    {
        return $this->is_paid === true;
    }

    /**
     * Check if bill is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Get total items count
     */
    public function getTotalItems()
    {
        return $this->orders->sum('quantity');
    }

    /**
     * Get items list formatted
     */
    public function getItemsList()
    {
        return $this->orders->map(function ($order) {
            return "{$order->quantity}x {$order->dish->name}";
        })->implode(', ');
    }

    /**
     * Calculate discount based on user membership
     */
    public function getDiscountAmount()
    {
        if (!$this->user || $this->isPaid()) {
            return 0;
        }

        $discountPercent = $this->user->getDiscountPercentage();
        return floor($this->total_amount * ($discountPercent / 100));
    }

    /**
     * Get final amount after discount
     */
    public function getFinalAmount()
    {
        $discount = $this->getDiscountAmount();
        
        // Add shipping for delivery
        $shipping = $this->isDelivery() ? 5000 : 0;

        return $this->total_amount - $discount + $shipping;
    }

    /**
     * Get payment status badge
     */
    public function getPaymentStatusColor()
    {
        return [
            'pending' => 'warning',
            'unpaid' => 'danger',
            'completed' => 'success',
            'cancelled' => 'secondary',
        ][$this->status] ?? 'secondary';
    }

    /**
     * Get readable status
     */
    public function getReadableStatus()
    {
        $statuses = [
            'pending' => 'Chờ xử lý',
            'unpaid' => 'Chưa thanh toán',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}