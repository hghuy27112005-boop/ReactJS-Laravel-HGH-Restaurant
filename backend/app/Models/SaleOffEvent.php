<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleOffEvent extends Model
{
    protected $table = 'sale_off_events';
    protected $primaryKey = 'sale_off_id';

    public $timestamps = true; // có created_at, updated_at theo migration

    protected $fillable = [
        'sale_off_percentage',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'sale_off_percentage' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}