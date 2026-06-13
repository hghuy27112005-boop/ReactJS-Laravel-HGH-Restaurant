<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistics extends Model
{
    protected $table = 'statistics';
    
    protected $fillable = [
        'user_id',
        'membership',
        'points_id',
        'total_orders',
        'booking_orders',
        'delivery_orders',
        'total_spent',
        'total_discount',
        'total_points',
        'last_order_date',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'booking_orders' => 'integer',
        'delivery_orders' => 'integer',
        'total_spent' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_points' => 'integer',
        'last_order_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function points()
    {
        return $this->hasMany(Points::class, 'user_id', 'user_id');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Increment total orders
     */
    public function incrementTotalOrders()
    {
        $this->total_orders += 1;
        $this->last_order_date = now();
        $this->save();
    }

    /**
     * Add spent amount
     */
    public function addSpent($amount)
    {
        $this->total_spent += $amount;
        $this->save();
    }

    /**
     * Add points
     */
    public function addPoints($points)
    {
        $this->total_points += $points;
        $this->save();
    }

    /**
     * Add discount
     */
    public function addDiscount($discount)
    {
        $this->total_discount += $discount;
        $this->save();
    }

    /**
     * Get order frequency
     */
    public function getOrderFrequency()
    {
        if ($this->total_orders == 0) {
            return 0;
        }

        $daysSinceCreated = $this->created_at->diffInDays(now());
        if ($daysSinceCreated == 0) {
            return $this->total_orders;
        }

        return round($this->total_orders / $daysSinceCreated, 2);
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValue()
    {
        if ($this->total_orders == 0) {
            return 0;
        }

        return $this->total_spent / $this->total_orders;
    }

    /**
     * Get booking vs delivery ratio
     */
    public function getOrderTypeRatio()
    {
        if ($this->total_orders == 0) {
            return ['booking' => 0, 'delivery' => 0];
        }

        return [
            'booking_percent' => round(($this->booking_orders / $this->total_orders) * 100, 2),
            'delivery_percent' => round(($this->delivery_orders / $this->total_orders) * 100, 2),
        ];
    }
}

    public function addSpent($amount)
    {
        $this->total_spent += $amount;
        $this->save();
    }

    public function addDiscount($amount)
    {
        $this->total_discount += $amount;
        $this->save();
    }

    public function addPoints($points)
    {
        $this->total_points += $points;
        $this->save();
    }

    public function getOrderFrequency()
    {
        if ($this->last_order_date) {
            $daysActive = now()->diffInDays($this->last_order_date);
            if ($daysActive > 0) {
                return $this->total_orders / $daysActive;
            }
        }
        return 0;
    }

    public function getAverageOrderValue()
    {
        if ($this->total_orders > 0) {
            return $this->total_spent / $this->total_orders;
        }
        return 0;
    }
}
