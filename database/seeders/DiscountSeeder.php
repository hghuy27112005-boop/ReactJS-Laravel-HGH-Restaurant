<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('authority', 'User')->get();
        
        $membershipDiscounts = [
            'Bronze' => 0,
            'Silver' => 5,
            'Gold' => 10,
            'Platinum' => 15,
            'Diamond' => 20,
        ];

        foreach ($users as $user) {
            $discountPercent = $membershipDiscounts[$user->membership] ?? 0;

            Discount::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'membership' => $user->membership,
                    'discount_percentage' => $discountPercent,
                    'discount_start_time' => now(),
                    'discount_end_time' => now()->addYear(),
                    'is_active' => true,
                    'description' => $user->membership . ' membership discount: ' . $discountPercent . '%',
                ]
            );
        }

        $this->command->info('✓ Discounts seeded successfully based on membership tiers');
    }
}
