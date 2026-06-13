<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleOffEvent extends Model
{
    protected $table = 'sale_off_events';
    
    protected $fillable = [
        'event_name',
        'sale_off_percentage',
        'description',
        'sale_off_start_time',
        'sale_off_end_time',
        'is_active',
    ];

    protected $casts = [
        'sale_off_percentage' => 'decimal:2',
        'sale_off_start_time' => 'datetime',
        'sale_off_end_time' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('sale_off_start_time', '<=', now())
            ->where('sale_off_end_time', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('sale_off_start_time', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('sale_off_end_time', '<', now());
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if event is valid and active
     */
    public function isValid()
    {
        return $this->is_active === true &&
               $this->sale_off_start_time <= now() &&
               $this->sale_off_end_time >= now();
    }

    /**
     * Check if event is expired
     */
    public function isExpired()
    {
        return $this->sale_off_end_time < now();
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming()
    {
        return $this->sale_off_start_time > now();
    }

    /**
     * Apply discount to price
     */
    public function applyDiscount($price)
    {
        if ($this->isValid()) {
            return $price * (1 - $this->sale_off_percentage / 100);
        }
        return $price;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($price)
    {
        if ($this->isValid()) {
            return floor($price * ($this->sale_off_percentage / 100));
        }
        return 0;
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays()
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->sale_off_end_time);
    }

    /**
     * Get remaining hours
     */
    public function getRemainingHours()
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInHours($this->sale_off_end_time);
    }

    /**
     * Get event status
     */
    public function getStatus()
    {
        if ($this->isUpcoming()) {
            return 'upcoming';
        } elseif ($this->isValid()) {
            return 'active';
        } elseif ($this->isExpired()) {
            return 'expired';
        }

        return 'inactive';
    }

    /**
     * Get event info
     */
    public function getEventInfo()
    {
        return [
            'name' => $this->event_name,
            'discount' => $this->sale_off_percentage . '%',
            'status' => $this->getStatus(),
            'start' => $this->sale_off_start_time->format('Y-m-d H:i'),
            'end' => $this->sale_off_end_time->format('Y-m-d H:i'),
            'remaining_days' => $this->getRemainingDays(),
            'remaining_hours' => $this->getRemainingHours(),
        ];
    }
    }

    public function getDiscountAmount($price)
    {
        if ($this->isValid()) {
            return $price * ($this->sale_off_percentage / 100);
        }
        return 0;
    }

    public function getStatus()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if (now() < $this->sale_off_start_time) {
            return 'Upcoming';
        } elseif (now() > $this->sale_off_end_time) {
            return 'Expired';
        } else {
            return 'Active';
        }
    }
}
