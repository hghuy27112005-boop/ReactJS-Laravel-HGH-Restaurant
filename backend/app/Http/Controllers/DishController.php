<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dish;

class DishController extends Controller
{
    public function index(Request $request)
    {
        $dishes = Dish::all();

        if ($request->expectsJson()) {
            $dishes->each(function ($dish) {
                $dish->image_url = $dish->image_url;
            });

            return response()->json($dishes);
        }

        return view('menu', ['danhSachMon' => $dishes]);
    }

    public function show(Request $request, $id)
    {
        $dish = Dish::find($id);
        if (!$dish) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không tìm thấy món ăn'], 404);
            }
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json($dish);
        }

        $allDishes = Dish::orderBy('dish_id', 'asc')->get();

        return view('dish-detail', [
            'mon' => $dish,
            'allDishes' => $allDishes,
        ]);
    }

    public function menuManagement()
    {
        $dishes = Dish::orderBy('dish_id', 'asc')->get();
        $dishTypes = \Illuminate\Support\Facades\DB::table('dish_types')->get();
        return view('menu_management', compact('dishes', 'dishTypes'));
    }

    public function addDish(Request $request)
    {
        $request->validate([
            'dish_name' => 'required|string|max:255',
            'type_id' => 'required|exists:dish_types,type_id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                
                // Logic đặt tên theo số thứ tự (X.jpg)
                $directory = public_path('dishes');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                $files = glob($directory . '/*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
                $maxNum = 0;
                foreach ($files as $f) {
                    $nameOnly = pathinfo($f, PATHINFO_FILENAME);
                    if (is_numeric($nameOnly)) {
                        $maxNum = max($maxNum, (int)$nameOnly);
                    }
                }
                
                $nextNum = $maxNum + 1;
                $newFilename = $nextNum . '.' . $extension;
                
                $file->move($directory, $newFilename);

                Dish::create([
                    'dish_name' => $request->dish_name,
                    'price' => 30000,
                    'image_url' => $newFilename,
                    'type_id' => $request->type_id,
                    'is_bestseller' => false,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Thêm món ăn thành công!'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDish(Request $request, $id)
    {
        $dish = Dish::findOrFail($id);

        $request->validate([
            'dish_name' => 'required|string|max:255',
            'type_id' => 'required|exists:dish_types,type_id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            $data = [
                'dish_name' => $request->dish_name,
                'type_id' => $request->type_id,
                'is_bestseller' => $request->has('is_bestseller'),
            ];

            if ($request->hasFile('image')) {
                // Xóa hình cũ nếu tồn tại trong folder dishes
                if ($dish->image_url) {
                    $oldPath = public_path('dishes/' . $dish->image_url);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();

                // Dùng ID làm tên file để giữ STT hoặc dùng logic đếm lại đều được
                // Ở đây mình sẽ dùng ID để đảm bảo tính duy nhất
                $newFilename = $dish->dish_id . '.' . $extension;
                $file->move(public_path('dishes'), $newFilename);
                $data['image_url'] = $newFilename;
            }

            $dish->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật món ăn thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteDish($id)
    {
        try {
            $dish = Dish::findOrFail($id);

            // Xóa file hình trong folder dishes
            if ($dish->image_url) {
                $filePath = public_path('dishes/' . $dish->image_url);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $dish->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa món ăn thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}