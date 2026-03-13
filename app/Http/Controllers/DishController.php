<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dish;

class DishController extends Controller
{
    public function index()
    {
        $dishes = Dish::all();

        return view('menu', ['danhSachMon' => $dishes]);
    }

    public function show($id)
    {
        $dish = Dish::find($id);

        if (!$dish) {
            abort(404);
        }

        return view('dish-detail', ['mon' => $dish]);
    }

    public function menuManagement()
    {
        $dishes = Dish::orderBy('dish_id', 'asc')->get();
        return view('menu_management', compact('dishes'));
    }
}