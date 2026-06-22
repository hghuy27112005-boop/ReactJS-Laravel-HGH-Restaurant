<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Points extends Model
{
    protected $table = 'points';
    protected $primaryKey = 'point_id';

    public $timestamps = false; // Chỉ có created_at, không có updated_at

    protected $fillable = [
        'user_id',
        'bill_id',
        'points_earned',
        'booking_total_price',
        'delivery_total_price',
    ];

    protected $casts = [
        'points_earned'         => 'integer',
        'booking_total_price'   => 'decimal:2',
        'delivery_total_price'  => 'decimal:2',
        'created_at'            => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Tính điểm thưởng dựa trên số tiền thanh toán.
     *
     * Quy tắc tạm: 1 điểm cho mỗi 10,000đ, làm tròn xuống.
     * Đây là giá trị placeholder — sửa lại công thức khi có quy định
     * điểm thưởng thật (ví dụ theo membership, theo sự kiện khuyến mãi...).
     */
    public static function calculatePoints(float $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        return (int) floor($amount / 10000);
    }
}
