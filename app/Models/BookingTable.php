<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingTable extends Model
{
    protected $table = 'booking_tables';
    public $timestamps = false;

    protected $fillable = [
        'table_number',
        'booking_date',
        'arrival_time',
        'finish_time',
        'table_type',
        'customer_name',
        'customer_phone',
        'start_time',
        'end_time',
        'bill_id',
        'created_at'
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'id');
    }
}
