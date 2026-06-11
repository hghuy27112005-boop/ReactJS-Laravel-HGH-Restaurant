<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    public $timestamps = false;
    
    protected $fillable = [
        'order_code',
        'bill_id',
        'dish_id',
        'quantity',
        'price_at_order',
        'order_type',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_order' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'id');
    }

    public function dish()
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Generate unique order code (DDMMYY_SEQUENCE)
     */
    public function generateOrderCode()
    {
        $today = now();
        $prefix = $today->format('dmy'); // DDMMYY
        
        $count = Order::whereDate('created_at', $today)
            ->count() + 1;
        
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        $this->order_code = $prefix . '_' . $sequence;
        $this->save();
    }

    /**
     * Get total price for this order item
     */
    public function getTotalPrice()
    {
        return $this->price_at_order * $this->quantity;
    }

    /**
     * Get item display info
     */
    public function getItemInfo()
    {
        return [
            'dish_name' => $this->dish->dish_name ?? 'Unknown',
            'quantity' => $this->quantity,
            'price_at_order' => $this->price_at_order,
            'total' => $this->getTotalPrice(),
            'note' => $this->note,
        ];
    }

    /**
     * Recalculate bill total after quantity change
     */
    public function recalculateBillTotal()
    {
        if ($this->bill) {
            $billTotal = $this->bill->orders->sum(function ($order) {
                return $order->getTotalPrice();
            });
            $this->bill->total_amount = $billTotal;
            $this->bill->save();
        }
    }

    /**
     * Update stock when order placed
     */
    public function updateStock()
    {
        if ($this->dish) {
            $stock = Stock::where('dish_id', $this->dish_id)->first();
            if ($stock) {
                $stock->decreaseQuantity($this->quantity);
            }
        }
    }
    }

    public function getTotalPrice()
    {
        return $this->quantity * $this->price_at_order;
    }
}
