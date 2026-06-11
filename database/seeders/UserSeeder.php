<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Statistics;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@restaurant.test'],
            [
                'name' => 'Admin User',
                'email' => 'admin@restaurant.test',
                'password' => Hash::make('password123'),
                'phone' => '0912345678',
                'authority' => 'Admin',
                'membership' => 'Diamond',
                'points_accumulated' => 50000,
            ]
        );

        // Create statistics for admin
        Statistics::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'user_id' => $admin->id,
                'total_orders' => 0,
                'booking_orders' => 0,
                'delivery_orders' => 0,
                'total_spent' => 0,
                'total_discount' => 0,
                'total_points' => 0,
            ]
        );

        // Create regular users
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '0901234567',
                'membership' => 'Diamond',
                'points' => 50000,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '0902345678',
                'membership' => 'Platinum',
                'points' => 10500,
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@example.com',
                'phone' => '0903456789',
                'membership' => 'Gold',
                'points' => 5200,
            ],
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'phone' => '0904567890',
                'membership' => 'Silver',
                'points' => 1500,
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
                'phone' => '0905678901',
                'membership' => 'Bronze',
                'points' => 300,
            ],
            [
                'name' => 'Diana Ross',
                'email' => 'diana@example.com',
                'phone' => '0906789012',
                'membership' => 'Gold',
                'points' => 6800,
            ],
            [
                'name' => 'Edward Norton',
                'email' => 'edward@example.com',
                'phone' => '0907890123',
                'membership' => 'Silver',
                'points' => 2100,
            ],
            [
                'name' => 'Fiona Apple',
                'email' => 'fiona@example.com',
                'phone' => '0908901234',
                'membership' => 'Bronze',
                'points' => 100,
            ],
            [
                'name' => 'George Miller',
                'email' => 'george@example.com',
                'phone' => '0909012345',
                'membership' => 'Platinum',
                'points' => 12500,
            ],
            [
                'name' => 'Hannah Montana',
                'email' => 'hannah@example.com',
                'phone' => '0910123456',
                'membership' => 'Diamond',
                'points' => 55000,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password123'),
                    'phone' => $userData['phone'],
                    'authority' => 'User',
                    'membership' => $userData['membership'],
                    'points_accumulated' => $userData['points'],
                ]
            );

            // Create statistics for each user
            Statistics::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'total_orders' => rand(5, 50),
                    'booking_orders' => rand(1, 10),
                    'delivery_orders' => rand(1, 10),
                    'total_spent' => rand(500000, 5000000),
                    'total_discount' => rand(0, 500000),
                    'total_points' => $userData['points'],
                ]
            );
        }

        $this->command->info('✓ Users seeded successfully (1 admin + 10 users created)');
    }
}
