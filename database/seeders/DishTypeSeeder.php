<?php

namespace Database\Seeders;

use App\Models\DishType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DishTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['type_name' => 'Pizza'],
            ['type_name' => 'Pasta'],
            ['type_name' => 'Burger'],
            ['type_name' => 'Salad'],
            ['type_name' => 'Dessert'],
            ['type_name' => 'Beverage'],
            ['type_name' => 'Appetizer'],
            ['type_name' => 'Asian Cuisine'],
        ];

        foreach ($types as $type) {
            DishType::updateOrCreate(
                ['type_name' => $type['type_name']],
                $type
            );
        }

        $this->command->info('✓ DishTypes seeded successfully');
    }
}
