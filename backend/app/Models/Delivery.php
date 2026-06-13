<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $table = 'deliveries';
    protected $fillable = [
        'delivery_code',
        'user_id',
        'bill_id',
        'order_type',
        'address',
        'status',
        'payment_method',
        'total_price',
        'shipping_fee',
        'final_price',
        'is_paid',
        'approved_at',
        'delivery_started_at',
        'delivered_at',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'approved_at' => 'datetime',
        'delivery_started_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'id');
    }

    public function points()
    {
        return $this->hasMany(Points::class, 'delivery_id', 'id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInDelivery($query)
    {
        return $query->where('status', 'in_delivery');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if delivery is pending approval
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if delivery is in progress
     */
    public function isInDelivery()
    {
        return $this->status === 'in_delivery';
    }

    /**
     * Check if delivery is completed
     */
    public function isDelivered()
    {
        return $this->status === 'delivered';
    }

    /**
     * Get estimated delivery time (based on address distance)
     */
    public function getEstimatedDeliveryTime()
    {
        // Simple estimation: 30 minutes base + 5 minutes per km
        // In production, use real distance API
        return 30;
    }

    /**
     * Calculate delivery time taken (if delivered)
     */
    public function getDeliveryTimeTaken()
    {
        if (!$this->delivery_started_at || !$this->delivered_at) {
            return null;
        }

        return $this->delivered_at->diffInMinutes($this->delivery_started_at);
    }

    /**
     * Check if delivery is late
     */
    public function isLate()
    {
        if (!$this->isDelivered()) {
            return false;
        }

        $timeTaken = $this->getDeliveryTimeTaken();
        $estimatedTime = $this->getEstimatedDeliveryTime();

        return $timeTaken > $estimatedTime;
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColor()
    {
        return [
            'pending' => 'warning',
            'approved' => 'info',
            'in_delivery' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
        ][$this->status] ?? 'secondary';
    }
}

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('status', 'awaiting_approval');
    }

    public function scopeInDelivery($query)
    {
        return $query->where('status', 'in_delivery');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    // Methods
    public function generateDeliveryCode()
    {
        $today = now();
        $prefix = $today->format('dmy'); // DDMMYY
        
        $count = Delivery::whereDate('created_at', $today)
            ->count() + 1;
        
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        $this->delivery_code = $prefix . $sequence;
        $this->save();
    }

    public function approve()
    {
        $this->status = 'awaiting_approval';
        $this->approved_at = now();
        $this->save();
    }

    public function startDelivery()
    {
        $this->status = 'in_delivery';
        $this->delivery_started_at = now();
        $this->save();
    }

    public function markAsDelivered()
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();
    }

    public function calculateFinalPrice()
    {
        $this->final_price = $this->total_price + $this->shipping_fee;
        $this->save();
    }
}
