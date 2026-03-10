<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $table = 'bills';

    const UPDATED_AT = null;

    protected $fillable = [
        'bill_code', 
        'order_in_day', 
        'customer_name', 
        'total_amount', 
        'order_type', 
        'address', 
        'bill_code',
        'order_in_day',
        'customer_name',
        'total_amount',
        'order_type',
        'address',
        'table_number',
        'booking_date',
        'arrival_time',
        'finish_time',
        'status',
        'is_paid',
        'payment_method',
        'paid_at',
        'created_at',
    ];

    public function details()
    {
        return $this->hasMany(BillDetail::class, 'bill_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(BookingTable::class, 'bill_id', 'id');
    }
}