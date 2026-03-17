<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public $timestamps = true;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'phone',
        'role',
        'avatar_url',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function syncAvatar()
    {
        $avatarPath = public_path('avatars/' . $this->user_id);

        if (file_exists($avatarPath) && is_dir($avatarPath)) {
            $files = glob($avatarPath . '/*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);

            if (!empty($files)) {
                // Sắp xếp theo thời gian sửa đổi (mới nhất lên đầu)
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                $latestFile = basename($files[0]);
                $this->avatar_url = asset('avatars/' . $this->user_id . '/' . $latestFile);
            }
        }
        else {
            // Nếu không có folder (do mới seed hoặc mới tạo)
            // Nếu avatar_url hiện tại là link local (trỏ vào folder avatars của chính user đó)
            // thì reset về null vì folder đã bị xóa rồi.
            if ($this->avatar_url && str_contains($this->avatar_url, asset('avatars/' . $this->user_id))) {
                $this->avatar_url = null;
            }
        }

        $this->save();
    }

    public function getAvatarUrlAttribute($value)
    {
        if ($value) return $value;
        // Nếu là admin mà chưa có avatar riêng, trả về logo apple
        if ($this->role === 'admin') return asset('hgh-apple.png');
        // Còn lại trả về ảnh mặc định chung
        return asset('pics/default_avt.jpg');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class , 'user_id', 'user_id');
    }
}