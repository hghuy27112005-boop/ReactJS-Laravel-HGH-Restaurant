<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Stock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dishes = Dish::all();

        foreach ($dishes as $dish) {
            // Create or update stock for each dish
            Stock::updateOrCreate(
                ['dish_id' => $dish->dish_id],
                [
                    'dish_id' => $dish->dish_id,
                    'quantity_start' => rand(50, 200),
                    'quantity_left' => rand(30, 150),
                    'stock_date' => now()->format('Y-m-d'),
                    'status' => rand(1, 100) > 20 ? 'active' : 'low_stock',
                    'note' => 'Initial stock for ' . $dish->dish_name,
                    'cost_per_unit' => floor($dish->price * 0.4), // Assume cost is 40% of price
                ]
            );
        }

        $this->command->info('✓ Stock seeded successfully for all dishes');
    }
}
