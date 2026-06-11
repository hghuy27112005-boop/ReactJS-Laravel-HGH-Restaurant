<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Added this line

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Xóa sạch thư mục avatars trong public trước khi seed
        $avatarPath = public_path('avatars');
        if (file_exists($avatarPath)) {
            \Illuminate\Support\Facades\File::cleanDirectory($avatarPath);
        }

        // Xử lý thư mục dishes: Xóa sạch đồ cũ, copy lại 12 ảnh gốc từ kho lưu trữ (folder pics)
        $dishPath = public_path('dishes');
        $dishBackupPath = public_path('pics');

        if (!file_exists($dishPath)) {
            mkdir($dishPath, 0755, true);
        }
        else {
            \Illuminate\Support\Facades\File::cleanDirectory($dishPath);
        }

        if (file_exists($dishBackupPath)) {
            for ($i = 1; $i <= 12; $i++) {
                $sourceName = str_pad($i, 2, '0', STR_PAD_LEFT) . '.jpg'; // '01.jpg', '02.jpg'...
                $targetName = $i . '.jpg'; // '1.jpg', '2.jpg'...

                $sourceFile = $dishBackupPath . '/' . $sourceName;
                $targetFile = $dishPath . '/' . $targetName;

                if (file_exists($sourceFile)) {
                    \Illuminate\Support\Facades\File::copy($sourceFile, $targetFile);
                }
            }
        }

        // Xóa dữ liệu cũ trước khi seed (thứ tự quan trọng do khóa ngoại)
        DB::table('discounts')->delete();
        DB::table('sale_off_events')->delete();
        DB::table('stocks')->delete();
        DB::table('bill_details')->delete();
        DB::table('booking_tables')->delete();
        DB::table('deliveries')->delete();
        DB::table('orders')->delete();
        DB::table('bills')->delete();
        DB::table('points')->delete();
        DB::table('statistics')->delete();
        DB::table('staff')->delete();
        DB::table('dishes')->delete();
        DB::table('dish_types')->delete();
        DB::table('users')->delete();

        // Run seeders trong đúng thứ tự
        $this->call([
            DishTypeSeeder::class,
            DishSeeder::class,
            UserSeeder::class,
            StockSeeder::class,
            SaleOffEventSeeder::class,
            DiscountSeeder::class,
            StaffSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('======================================');
        $this->command->info('✓ DATABASE SEEDED SUCCESSFULLY!');
        $this->command->info('======================================');
        $this->command->info('');
        $this->command->info('📊 DATA CREATED:');
        $this->command->info('  • 8 Dish Types');
        $this->command->info('  • 30 Dishes');
        $this->command->info('  • 1 Admin + 10 Users');
        $this->command->info('  • Stock for all dishes');
        $this->command->info('  • 6 Sale Off Events');
        $this->command->info('  • Discounts for each user');
        $this->command->info('  • 11 Staff members');
        $this->command->info('');
        $this->command->info('🔑 TEST CREDENTIALS:');
        $this->command->info('  Admin:');
        $this->command->info('    Email: admin@restaurant.test');
        $this->command->info('    Password: password123');
        $this->command->info('');
        $this->command->info('  Regular User:');
        $this->command->info('    Email: john@example.com');
        $this->command->info('    Password: password123');
        $this->command->info('');
        $this->command->info('======================================');
        $this->command->info('');
    }
}
