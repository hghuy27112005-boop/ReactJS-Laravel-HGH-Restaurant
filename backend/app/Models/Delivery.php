<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $table = 'deliveries';
    
    protected $primaryKey = 'delivery_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'delivery_id',
        'order_id',
        'address',
        'D_payment_status',
        'delivery_status',
        'approved_at',
        'delivered_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Get user through order relationship
    public function getCustomerInfo()
    {
        return $this->order?->user;
    }
}

