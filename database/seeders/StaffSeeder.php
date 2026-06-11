<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffMembers = [
            // Managers
            [
                'name' => 'Manager 1',
                'email' => 'manager1@restaurant.test',
                'phone' => '0911111111',
                'position' => 'Manager',
                'status' => 'active',
                'hire_date' => now()->subYears(3),
            ],
            [
                'name' => 'Manager 2',
                'email' => 'manager2@restaurant.test',
                'phone' => '0912222222',
                'position' => 'Manager',
                'status' => 'active',
                'hire_date' => now()->subYears(2),
            ],
            // Chefs
            [
                'name' => 'Chef 1',
                'email' => 'chef1@restaurant.test',
                'phone' => '0913333333',
                'position' => 'Chef',
                'status' => 'active',
                'hire_date' => now()->subYears(2),
            ],
            [
                'name' => 'Chef 2',
                'email' => 'chef2@restaurant.test',
                'phone' => '0914444444',
                'position' => 'Chef',
                'status' => 'active',
                'hire_date' => now()->subYears(1),
            ],
            [
                'name' => 'Chef 3',
                'email' => 'chef3@restaurant.test',
                'phone' => '0915555555',
                'position' => 'Chef',
                'status' => 'active',
                'hire_date' => now()->subMonths(6),
            ],
            // Waiters
            [
                'name' => 'Waiter 1',
                'email' => 'waiter1@restaurant.test',
                'phone' => '0916666666',
                'position' => 'Waiter',
                'status' => 'active',
                'hire_date' => now()->subMonths(8),
            ],
            [
                'name' => 'Waiter 2',
                'email' => 'waiter2@restaurant.test',
                'phone' => '0917777777',
                'position' => 'Waiter',
                'status' => 'active',
                'hire_date' => now()->subMonths(6),
            ],
            [
                'name' => 'Waiter 3',
                'email' => 'waiter3@restaurant.test',
                'phone' => '0918888888',
                'position' => 'Waiter',
                'status' => 'active',
                'hire_date' => now()->subMonths(4),
            ],
            // Delivery Staff
            [
                'name' => 'Delivery 1',
                'email' => 'delivery1@restaurant.test',
                'phone' => '0919999999',
                'position' => 'Delivery',
                'status' => 'active',
                'hire_date' => now()->subMonths(3),
            ],
            [
                'name' => 'Delivery 2',
                'email' => 'delivery2@restaurant.test',
                'phone' => '0920000000',
                'position' => 'Delivery',
                'status' => 'active',
                'hire_date' => now()->subMonths(2),
            ],
            // Resigned staff
            [
                'name' => 'Former Staff',
                'email' => 'former@restaurant.test',
                'phone' => '0921111111',
                'position' => 'Waiter',
                'status' => 'resigned',
                'hire_date' => now()->subYears(2),
            ],
        ];

        foreach ($staffMembers as $staff) {
            Staff::updateOrCreate(
                ['email' => $staff['email']],
                $staff
            );
        }

        $this->command->info('✓ Staff seeded successfully (11 staff members created)');
    }
}
