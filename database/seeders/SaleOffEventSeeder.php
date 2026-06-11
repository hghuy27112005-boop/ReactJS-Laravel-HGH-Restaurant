<?php

namespace Database\Seeders;

use App\Models\SaleOffEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SaleOffEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'event_name' => 'Summer Sale 2024',
                'sale_off_percentage' => 10,
                'description' => 'Summer special: 10% off on all pizzas',
                'sale_off_start_time' => now(),
                'sale_off_end_time' => now()->addMonth(),
                'is_active' => true,
            ],
            [
                'event_name' => 'Weekend Special',
                'sale_off_percentage' => 15,
                'description' => 'Every weekend: 15% discount on beverages',
                'sale_off_start_time' => now(),
                'sale_off_end_time' => now()->addWeeks(2),
                'is_active' => true,
            ],
            [
                'event_name' => 'Happy Hour',
                'sale_off_percentage' => 20,
                'description' => '5PM-7PM: 20% off on all drinks',
                'sale_off_start_time' => now(),
                'sale_off_end_time' => now()->addMonth(),
                'is_active' => true,
            ],
            [
                'event_name' => 'Dessert Day',
                'sale_off_percentage' => 25,
                'description' => 'Every Wednesday: 25% off on desserts',
                'sale_off_start_time' => now(),
                'sale_off_end_time' => now()->addWeeks(4),
                'is_active' => true,
            ],
            [
                'event_name' => 'Valentine Special',
                'sale_off_percentage' => 30,
                'description' => 'Limited time: 30% off for couples',
                'sale_off_start_time' => now()->subMonth(),
                'sale_off_end_time' => now()->subDays(5),
                'is_active' => false,
            ],
            [
                'event_name' => 'New Year Promo',
                'sale_off_percentage' => 35,
                'description' => 'New Year celebration: 35% off on all items',
                'sale_off_start_time' => now()->addMonths(2),
                'sale_off_end_time' => now()->addMonths(3),
                'is_active' => false,
            ],
        ];

        foreach ($events as $event) {
            SaleOffEvent::updateOrCreate(
                ['event_name' => $event['event_name']],
                $event
            );
        }

        $this->command->info('✓ Sale Off Events seeded successfully (6 events created)');
    }
}
