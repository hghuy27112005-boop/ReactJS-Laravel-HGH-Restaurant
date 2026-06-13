<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discounts';
    
    protected $fillable = [
        'user_id',
        'membership',
        'discount_percentage',
        'discount_start_time',
        'discount_end_time',
        'is_active',
        'description',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'discount_start_time' => 'datetime',
        'discount_end_time' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('discount_start_time', '<=', now())
            ->where('discount_end_time', '>=', now());
    }

    public function scopeByMembership($query, $membership)
    {
        return $query->where('membership', $membership);
    }

    public function scopeExpired($query)
    {
        return $query->where('discount_end_time', '<', now());
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if discount is valid and active
     */
    public function isValid()
    {
        return $this->is_active === true &&
               $this->discount_start_time <= now() &&
               $this->discount_end_time >= now();
    }

    /**
     * Check if discount is expired
     */
    public function isExpired()
    {
        return $this->discount_end_time < now();
    }

    /**
     * Check if discount is pending (not started yet)
     */
    public function isPending()
    {
        return $this->discount_start_time > now();
    }

    /**
     * Calculate discount amount from price
     */
    public function calculateDiscount($price)
    {
        if (!$this->isValid()) {
            return 0;
        }

        return floor($price * ($this->discount_percentage / 100));
    }

    /**
     * Get final price after discount
     */
    public function applyDiscount($price)
    {
        return $price - $this->calculateDiscount($price);
    }

    /**
     * Get remaining days until expiry
     */
    public function getRemainingDays()
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->discount_end_time);
    }

    /**
     * Get discount info
     */
    public function getDiscountInfo()
    {
        return [
            'membership' => $this->membership,
            'percentage' => $this->discount_percentage,
            'valid' => $this->isValid(),
            'start' => $this->discount_start_time->format('Y-m-d'),
            'end' => $this->discount_end_time->format('Y-m-d'),
            'remaining_days' => $this->getRemainingDays(),
        ];
    }
    {
        return $this->is_active &&
               $this->discount_start_time <= now() &&
               $this->discount_end_time >= now();
    }

    public function applyDiscount($price)
    {
        if ($this->isValid()) {
            return $price * (1 - $this->discount_percentage / 100);
        }
        return $price;
    }

    public function getDiscountAmount($price)
    {
        if ($this->isValid()) {
            return $price * ($this->discount_percentage / 100);
        }
        return 0;
    }
}
