<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    protected $table = 'dishes';
    protected $primaryKey = 'dish_id';
    public $timestamps = false;

    protected $fillable = [
        'dish_name',
        'price',
        'image_url',
        'type_id',
        'is_bestseller',
    ];

    public function getImageUrlAttribute($value)
    {
        if (!$value)
            return asset('images/default-dish.png');
        if (str_starts_with($value, 'http'))
            return $value;
        return asset('dishes/' . $value);
    }

    public function getDishNameAttribute($value)
    {
        if (!$value)
            return $value;
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    public function type()
    {
        return $this->belongsTo(DishType::class , 'type_id', 'type_id');
    }
}
