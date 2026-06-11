<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\DishType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DishSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dishes = [
            // Pizza
            ['name' => 'Margherita Pizza', 'type' => 'Pizza', 'price' => 80000, 'bestseller' => true],
            ['name' => 'Pepperoni Pizza', 'type' => 'Pizza', 'price' => 90000, 'bestseller' => true],
            ['name' => 'Vegetarian Pizza', 'type' => 'Pizza', 'price' => 75000, 'bestseller' => false],
            ['name' => 'BBQ Chicken Pizza', 'type' => 'Pizza', 'price' => 95000, 'bestseller' => true],
            ['name' => 'Four Cheese Pizza', 'type' => 'Pizza', 'price' => 100000, 'bestseller' => false],

            // Pasta
            ['name' => 'Spaghetti Carbonara', 'type' => 'Pasta', 'price' => 85000, 'bestseller' => true],
            ['name' => 'Fettuccine Alfredo', 'type' => 'Pasta', 'price' => 80000, 'bestseller' => true],
            ['name' => 'Penne Arrabbiata', 'type' => 'Pasta', 'price' => 75000, 'bestseller' => false],
            ['name' => 'Lasagna', 'type' => 'Pasta', 'price' => 95000, 'bestseller' => true],
            ['name' => 'Ravioli Ricotta', 'type' => 'Pasta', 'price' => 88000, 'bestseller' => false],

            // Burger
            ['name' => 'Classic Burger', 'type' => 'Burger', 'price' => 65000, 'bestseller' => true],
            ['name' => 'Cheese Burger', 'type' => 'Burger', 'price' => 70000, 'bestseller' => true],
            ['name' => 'Bacon Burger', 'type' => 'Burger', 'price' => 85000, 'bestseller' => true],
            ['name' => 'Double Burger', 'type' => 'Burger', 'price' => 95000, 'bestseller' => false],
            ['name' => 'Veggie Burger', 'type' => 'Burger', 'price' => 60000, 'bestseller' => false],

            // Salad
            ['name' => 'Caesar Salad', 'type' => 'Salad', 'price' => 55000, 'bestseller' => true],
            ['name' => 'Greek Salad', 'type' => 'Salad', 'price' => 60000, 'bestseller' => true],
            ['name' => 'Caprese Salad', 'type' => 'Salad', 'price' => 70000, 'bestseller' => false],
            ['name' => 'Chicken Salad', 'type' => 'Salad', 'price' => 75000, 'bestseller' => true],

            // Dessert
            ['name' => 'Chocolate Cake', 'type' => 'Dessert', 'price' => 45000, 'bestseller' => true],
            ['name' => 'Tiramisu', 'type' => 'Dessert', 'price' => 50000, 'bestseller' => true],
            ['name' => 'Cheesecake', 'type' => 'Dessert', 'price' => 55000, 'bestseller' => false],
            ['name' => 'Ice Cream', 'type' => 'Dessert', 'price' => 30000, 'bestseller' => true],
            ['name' => 'Brownie', 'type' => 'Dessert', 'price' => 35000, 'bestseller' => false],

            // Beverage
            ['name' => 'Coca Cola', 'type' => 'Beverage', 'price' => 15000, 'bestseller' => true],
            ['name' => 'Orange Juice', 'type' => 'Beverage', 'price' => 20000, 'bestseller' => true],
            ['name' => 'Coffee', 'type' => 'Beverage', 'price' => 25000, 'bestseller' => true],
            ['name' => 'Iced Tea', 'type' => 'Beverage', 'price' => 18000, 'bestseller' => false],
            ['name' => 'Smoothie', 'type' => 'Beverage', 'price' => 35000, 'bestseller' => true],

            // Appetizer
            ['name' => 'French Fries', 'type' => 'Appetizer', 'price' => 25000, 'bestseller' => true],
            ['name' => 'Chicken Wings', 'type' => 'Appetizer', 'price' => 45000, 'bestseller' => true],
            ['name' => 'Onion Rings', 'type' => 'Appetizer', 'price' => 30000, 'bestseller' => false],
            ['name' => 'Mozzarella Sticks', 'type' => 'Appetizer', 'price' => 35000, 'bestseller' => true],
        ];

        foreach ($dishes as $dish) {
            $type = DishType::where('type_name', $dish['type'])->first();
            
            if ($type) {
                Dish::updateOrCreate(
                    ['dish_name' => $dish['name']],
                    [
                        'dish_name' => $dish['name'],
                        'price' => $dish['price'],
                        'type_id' => $type->type_id,
                        'is_bestseller' => $dish['bestseller'],
                        'image_url' => 'https://via.placeholder.com/300x200?text=' . urlencode($dish['name']),
                    ]
                );
            }
        }

        $this->command->info('✓ Dishes seeded successfully (30 dishes created)');
    }
}
