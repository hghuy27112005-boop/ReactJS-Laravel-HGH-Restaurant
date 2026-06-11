<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillDetail extends Model
{
    protected $table = 'bill_details';

    protected $fillable = [
        'bill_id',
        'dish_id',
        'quantity',
        'price_at_time',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_time' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

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
     * Get total price for this line item
     */
    public function getTotalPrice()
    {
        return $this->price_at_time * $this->quantity;
    }

    /**
     * Get formatted total price
     */
    public function getFormattedTotal()
    {
        return number_format($this->getTotalPrice(), 0, ',', '.') . ' VND';
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedPrice()
    {
        return number_format($this->price_at_time, 0, ',', '.') . ' VND';
    }

    /**
     * Get item details
     */
    public function getItemDetails()
    {
        return [
            'dish_name' => $this->dish->dish_name ?? 'Unknown',
            'quantity' => $this->quantity,
            'unit_price' => $this->price_at_time,
            'total' => $this->getTotalPrice(),
            'note' => $this->note,
        ];
    }

    /**
     * Get item info for display
     */
    public function getDisplayInfo()
    {
        return [
            'name' => $this->dish->dish_name ?? 'Unknown',
            'qty' => $this->quantity,
            'price' => $this->getFormattedPrice(),
            'total' => $this->getFormattedTotal(),
        ];
    }

    /**
     * Get subtotal contribution
     */
    public function getSubtotalPercent()
    {
        if (!$this->bill || $this->bill->total_amount == 0) {
            return 0;
        }

        return round(($this->getTotalPrice() / $this->bill->total_amount) * 100, 1);
    }

    /**
     * Check if has discount applied
     */
    public function hasDiscount()
    {
        if (!$this->dish) {
            return false;
        }

        // Compare current price with default price
        return $this->price_at_time < $this->dish->price;
    }

    /**
     * Get discount amount if applied
     */
    public function getDiscountAmount()
    {
        if (!$this->dish || !$this->hasDiscount()) {
            return 0;
        }

        return ($this->dish->price - $this->price_at_time) * $this->quantity;
    }
}