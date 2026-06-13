<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Copy images if needed
        $dishPath = public_path('dishes');
        $dishBackupPath = public_path('pics');

        if (!file_exists($dishPath)) {
            mkdir($dishPath, 0755, true);
        }

        if (file_exists($dishBackupPath)) {
            for ($i = 1; $i <= 12; $i++) {
                $sourceName = str_pad($i, 2, '0', STR_PAD_LEFT) . '.jpg';
                $targetName = $i . '.jpg';
                $sourceFile = $dishBackupPath . '/' . $sourceName;
                $targetFile = $dishPath . '/' . $targetName;
                if (file_exists($sourceFile) && !file_exists($targetFile)) {
                    File::copy($sourceFile, $targetFile);
                }
            }
        }

        // 2. Clear old data safely (reverse order of foreign keys)
        DB::statement('SET session_replication_role = replica;'); // Disable FK checks in PostgreSQL temporarily
        DB::table('stocks')->delete();
        DB::table('order_items')->delete();
        DB::table('deliveries')->delete();
        DB::table('booking_tables')->delete();
        DB::table('bills')->delete();
        DB::table('orders')->delete();
        DB::table('sale_off_events')->delete();
        DB::table('dishes')->delete();
        DB::table('dish_types')->delete();
        DB::table('table_types')->delete();
        DB::table('users')->delete();
        DB::statement('SET session_replication_role = DEFAULT;'); // Re-enable FK checks

        // 3. Seed Users
        DB::table('users')->insert([
            [
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password_hash' => Hash::make('admin'),
                'tele_number' => '0907106674',
                'role' => 'admin',
                'membership' => 'administrator',
                'created_at' => now(),
            ],
            [
                'username' => 'user1',
                'email' => 'user1@gmail.com',
                'password_hash' => Hash::make('password'),
                'tele_number' => '0123456789',
                'role' => 'user',
                'membership' => 'bronze',
                'created_at' => now(),
            ]
        ]);

        // 4. Seed Table Types
        DB::table('table_types')->insert([
            ['table_type_name' => 'Bàn 5 người', 'capacity' => 2],
            ['table_type_name' => 'Bàn 10 người', 'capacity' => 4],
            ['table_type_name' => 'Bàn 15 người', 'capacity' => 8],
        ]);

        // 5. Seed Dish Types
        DB::table('dish_types')->insert([
            ['type_id' => 1, 'type_name' => 'Món mặn'],
            ['type_id' => 2, 'type_name' => 'Món rau'],
            ['type_id' => 3, 'type_name' => 'Món canh'],
        ]);

        // 6. Seed Dishes (From User's code)
        DB::table('dishes')->insert([
            ['dish_name' => 'Nem chiên', 'price' => 30000, 'image_url' => '1.jpg', 'type_id' => 1, 'is_bestseller' => true],
            ['dish_name' => 'Bánh cuốn', 'price' => 30000, 'image_url' => '2.jpg', 'type_id' => 1, 'is_bestseller' => true],
            ['dish_name' => 'Gỏi cuốn tôm thịt', 'price' => 30000, 'image_url' => '3.jpg', 'type_id' => 1, 'is_bestseller' => true],
            ['dish_name' => 'Canh chua cá quả', 'price' => 30000, 'image_url' => '4.jpg', 'type_id' => 3, 'is_bestseller' => true],
            ['dish_name' => 'Cơm cà ri thịt heo chiên xù', 'price' => 30000, 'image_url' => '5.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Lagu gà', 'price' => 30000, 'image_url' => '6.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Bò kho', 'price' => 30000, 'image_url' => '7.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Cơm chiên dương châu', 'price' => 30000, 'image_url' => '8.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Cơm chiên cá mặn', 'price' => 30000, 'image_url' => '9.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Đậu hũ nhồi thịt', 'price' => 30000, 'image_url' => '10.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Thịt ba chỉ kho', 'price' => 30000, 'image_url' => '11.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Bò xào đậu cove', 'price' => 30000, 'image_url' => '12.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Cơm rang dưa bò', 'price' => 30000, 'image_url' => '13.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Thịt kho tàu', 'price' => 30000, 'image_url' => '14.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Rau muống xào tỏi', 'price' => 30000, 'image_url' => '15.jpg', 'type_id' => 2, 'is_bestseller' => false],
            ['dish_name' => 'Súp lơ xào nấm', 'price' => 30000, 'image_url' => '16.jpg', 'type_id' => 2, 'is_bestseller' => false],
            ['dish_name' => 'Canh khoai mỡ', 'price' => 30000, 'image_url' => '17.jpg', 'type_id' => 3, 'is_bestseller' => false],
            ['dish_name' => 'Canh khổ qua nhồi thịt', 'price' => 30000, 'image_url' => '18.jpg', 'type_id' => 3, 'is_bestseller' => false],
            ['dish_name' => 'Gỏi cá lóc nướng', 'price' => 30000, 'image_url' => '19.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Gỏi đu đủ', 'price' => 30000, 'image_url' => '20.jpg', 'type_id' => 2, 'is_bestseller' => false],
            ['dish_name' => 'Tàu hủ sốt bơ me', 'price' => 30000, 'image_url' => '21.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Mì xào hải sản', 'price' => 30000, 'image_url' => '22.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Tôm chiên xù', 'price' => 30000, 'image_url' => '23.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Bún thịt nướng', 'price' => 30000, 'image_url' => '24.jpg', 'type_id' => 1, 'is_bestseller' => false],
            ['dish_name' => 'Súp cua', 'price' => 30000, 'image_url' => '25.jpg', 'type_id' => 3, 'is_bestseller' => false],
            ['dish_name' => 'Bánh xèo', 'price' => 30000, 'image_url' => '26.jpg', 'type_id' => 1, 'is_bestseller' => false],
        ]);

        // 7. Seed Stocks
        $dishes = DB::table('dishes')->get();
        foreach ($dishes as $dish) {
            DB::table('stocks')->insert([
                'dish_id' => $dish->dish_id,
                'quantity_start' => 200,
                'quantity_left' => 200,
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ CSDL mới đã được seed thành công với dữ liệu của bạn!');
    }
}
