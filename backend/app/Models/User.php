<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public $timestamps = true;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'tele_number',
        'avatar_url',
        'role',
        'membership',
         'provider',
        'provider_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function bills()
    {
        return $this->hasManyThrough(
            Bill::class,
            Order::class,
            'user_id',  // FK trên orders
            'order_id', // FK trên bills
            'user_id',  // PK trên users
            'order_id'  // PK trên orders
        );
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function points()
    {
        return $this->hasMany(Points::class, 'user_id', 'user_id');
    }

    public function statistics()
    {
        return $this->hasOne(Statistics::class, 'user_id', 'user_id');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Cộng điểm thưởng vào tổng điểm hiện có của user.
     *
     * Placeholder tạm: chưa có cột "points"/"total_points" riêng trên
     * bảng users theo migration hiện tại — nếu sau này thêm cột đó, sửa
     * lại hàm này để $this->increment('points', $amount). Hiện tại điểm
     * tổng được phản ánh qua Statistics::total_points (xem statistics()).
     */
    public function incrementPoints(int $amount): void
    {
        // Không làm gì thêm ở đây — Statistics::addPoints() trong
        // BillController/VnpayController đã là nguồn ghi nhận tổng điểm.
        // Giữ method này để các lời gọi hiện có không bị lỗi "method không tồn tại".
    }

    /**
     * Lấy (hoặc tạo nếu chưa có) bản ghi statistics của user này.
     * Dùng thay cho $user->statistics khi cần đảm bảo luôn có record,
     * tránh lỗi gọi method trên null ở lần thanh toán đầu tiên của user.
     */
    public function getOrCreateStatistics(): Statistics
    {
        return $this->statistics ?? Statistics::create(['user_id' => $this->user_id]);
    }
}