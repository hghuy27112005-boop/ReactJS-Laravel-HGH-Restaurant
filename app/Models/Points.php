<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Points extends Model
{
    protected $table = 'points';
    
    protected $fillable = [
        'user_id',
        'bill_id',
        'delivery_id',
        'booking_total_price',
        'delivery_total_price',
        'points_earned',
        'note',
    ];

    protected $casts = [
        'booking_total_price' => 'decimal:2',
        'delivery_total_price' => 'decimal:2',
        'points_earned' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'id');
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id', 'id');
    }

    // Methods
    public function calculatePoints()
    {
        $totalPrice = $this->booking_total_price + $this->delivery_total_price;
        // 1 ponto a cada 1000 VND
        $this->points_earned = (int) floor($totalPrice / 1000);
        $this->save();

        // Atualizar pontos acumulados do user
        $this->user->points_accumulated += $this->points_earned;
        $this->user->save();

        // Atualizar membership se necessário
        $this->user->updateMembership();

        // Atualizar statistics
        $stats = Statistics::firstOrCreate(['user_id' => $this->user_id]);
        $stats->total_points += $this->points_earned;
        $stats->save();
    }

    /**
     * Static method to calculate points from price
     */
    public static function calculatePoints($price)
    {
        return (int) floor($price / 1000);
    }

    public function getPointsFromPrice($price)
    {
        return (int) floor($price / 1000);
    }
}
