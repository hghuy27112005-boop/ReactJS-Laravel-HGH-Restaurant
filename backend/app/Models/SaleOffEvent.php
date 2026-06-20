<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleOffEvent extends Model
{
    protected $table = 'sale_off_events';
    protected $primaryKey = 'sale_off_id';

    protected $fillable = [
        'sale_off_percentage',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'sale_off_percentage' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // HELPER METHODS
    // ============================================

    public function isActive()
    {
        return $this->start_time <= now() && $this->end_time >= now();
    }

    public function applyDiscount($price)
    {
        if ($this->isActive()) {
            return $price * (1 - $this->sale_off_percentage / 100);
        }
        return $price;
    }
}

