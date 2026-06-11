<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stocks';
    
    protected $fillable = [
        'dish_id',
        'quantity_start',
        'quantity_left',
        'stock_date',
        'status',
        'note',
    ];

    protected $casts = [
        'quantity_start' => 'integer',
        'quantity_left' => 'integer',
        'stock_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function dish()
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->where('quantity_left', '<=', 15);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Decrease quantity when order is placed
     */
    public function decreaseQuantity($quantity)
    {
        $this->quantity_left -= $quantity;

        // Check if needs to be marked as low stock
        if ($this->quantity_left <= 15) {
            $this->status = 'low_stock';
        }

        // Check if out of stock
        if ($this->quantity_left <= 0) {
            $this->quantity_left = 0;
            $this->status = 'expired';
        }

        $this->save();
    }

    /**
     * Increase quantity when restocking
     */
    public function increaseQuantity($quantity)
    {
        $this->quantity_left += $quantity;
        $this->quantity_start += $quantity;

        // Reset status to active if restocking from low stock
        if ($this->status === 'low_stock' || $this->status === 'expired') {
            $this->status = 'active';
        }

        $this->save();
    }

    /**
     * Get percentage used
     */
    public function getPercentageUsed()
    {
        if ($this->quantity_start == 0) {
            return 0;
        }

        return round((($this->quantity_start - $this->quantity_left) / $this->quantity_start) * 100, 2);
    }

    /**
     * Get percentage left
     */
    public function getPercentageLeft()
    {
        if ($this->quantity_start == 0) {
            return 0;
        }

        return round(($this->quantity_left / $this->quantity_start) * 100, 2);
    }

    /**
     * Get total cost
     */
    public function getTotalCost()
    {
        return $this->quantity_start * $this->cost_per_unit;
    }

    /**
     * Get revenue from this stock (based on orders)
     */
    public function getRevenueFromDish()
    {
        return Order::whereHas('bill', function ($q) {
            $q->whereDate('created_at', '>=', $this->created_at);
        })
        ->where('dish_id', $this->dish_id)
        ->selectRaw('SUM(quantity * price_at_order) as total')
        ->first()
        ->total ?? 0;
    }

    /**
     * Get profit
     */
    public function getProfit()
    {
        return $this->getRevenueFromDish() - $this->getTotalCost();
    }

    /**
     * Check if needs restock alert
     */
    public function needsRestockAlert()
    {
        return $this->quantity_left <= 15 && $this->status === 'active';
    }
}
    public function decreaseQuantity($amount = 1)
    {
        $this->quantity_left -= $amount;
        
        if ($this->quantity_left <= 15) {
            $this->status = 'low_stock';
        }
        
        $this->save();
    }

    public function increaseQuantity($amount = 1)
    {
        $this->quantity_left += $amount;
        $this->status = 'active';
        $this->save();
    }

    public function getRevenueFromDish()
    {
        $soldQuantity = $this->quantity_start - $this->quantity_left;
        return $soldQuantity * $this->dish->price;
    }

    public function shouldReplace()
    {
        // Se sobrou >= 90 desde ontem
        return $this->quantity_left >= 90 && 
               $this->updated_at->addDay() < now();
    }

    public function markAsReplaced()
    {
        $this->status = 'replaced';
        $this->save();
    }
}
