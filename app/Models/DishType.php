<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DishType extends Model
{
    protected $table = 'dish_types';
    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $fillable = ['type_name'];

    public function dishes()
    {
        return $this->hasMany(Dish::class, 'type_id', 'type_id');
    }
}
