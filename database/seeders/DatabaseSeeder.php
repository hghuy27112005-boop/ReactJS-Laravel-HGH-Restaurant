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
        // Xóa dữ liệu cũ trước khi seed (truật tự quan trọng do khóa ngoại)
        DB::table('bill_details')->delete();
        DB::table('booking_tables')->delete();
        DB::table('bills')->delete();
        DB::table('dishes')->delete();
        DB::table('dish_types')->delete();

        // Seed Dish Types
        DB::table('dish_types')->insert([
            ['type_id' => 1, 'type_name' => 'Khai vị'],
            ['type_id' => 2, 'type_name' => 'Món chính'],
            ['type_id' => 3, 'type_name' => 'Tráng miệng'],
        ]);

        // Seed Dishes
        DB::table('dishes')->insert([
            ['dish_name' => 'Nem chiên', 'price' => 30000, 'image_url' => '01.jpg', 'type_id' => 1, 'is_bestseller' => true, 'description' => 'Nem rán giòn rụm với nhân thịt bằm và mộc nhĩ truyền thống.'],
            ['dish_name' => 'Bánh cuốn', 'price' => 30000, 'image_url' => '02.jpg', 'type_id' => 1, 'is_bestseller' => true, 'description' => 'Bánh cuốn mỏng mịn, thơm nồng hương hành phi.'],
            ['dish_name' => 'Gỏi cuốn tôm thịt', 'price' => 30000, 'image_url' => '03.jpg', 'type_id' => 1, 'is_bestseller' => true, 'description' => 'Gỏi cuốn tươi mát với tôm, thịt ba chỉ và rau sống thanh đạm.'],
            ['dish_name' => 'Canh chua cá quả', 'price' => 30000, 'image_url' => '04.jpg', 'type_id' => 1, 'is_bestseller' => true, 'description' => 'Canh chua cá quả đậm đà hương vị, chua cay hài hòa.'],
            ['dish_name' => 'Cơm cà ri thịt heo chiên xù', 'price' => 30000, 'image_url' => '05.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Thịt heo chiên xù giòn tan quyện cùng sốt cà ri Nhật bản đặc trưng.'],
            ['dish_name' => 'Lagu gà', 'price' => 30000, 'image_url' => '06.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Gà nấu lagu mềm thơm, dùng kèm bánh mì nóng hổi rất tuyệt vời.'],
            ['dish_name' => 'Bò kho', 'price' => 30000, 'image_url' => '07.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Bò kho đậm vị, miếng bò mềm tan cùng cà rốt và sả thơm nồng.'],
            ['dish_name' => 'Cơm chiên dương châu', 'price' => 30000, 'image_url' => '08.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Cơm chiên hạt tơi, đầy đủ màu sắc.'],
            ['dish_name' => 'Cơm chiên cá mặn', 'price' => 30000, 'image_url' => '09.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Hương vị biển cả đậm đà từ cá mặn quyện trong hạt cơm chiên giòn.'],
            ['dish_name' => 'Đậu hũ nhồi thịt', 'price' => 30000, 'image_url' => '10.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Đậu hũ nhồi thịt đậm đà, sốt cà chua thanh ngọt đưa cơm.'],
            ['dish_name' => 'Thịt ba chỉ kho', 'price' => 30000, 'image_url' => '11.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Thịt ba chỉ kho tàu mềm rục và béo ngậy.'],
            ['dish_name' => 'Bò xào đậu cove', 'price' => 30000, 'image_url' => '12.jpg', 'type_id' => 2, 'is_bestseller' => false, 'description' => 'Thịt bò xào nhanh tay với đậu cove xanh mướt, giữ độ giòn ngọt.'],
        ]);

        // Seed Users
        DB::table('users')->insert([
            [
                'user_id' => 1,
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password_hash' => bcrypt('admin'),
                'phone' => '0907106674',
                'role' => 'admin',
                'created_at' => now(),
            ],
            [
                'user_id' => 2,
                'username' => 'user_no.2',
                'email' => 'user_no.2@gmail.com',
                'password_hash' => bcrypt('user_no.2'),
                'phone' => '0912783456',
                'role' => 'user',
                'created_at' => now(),
            ],
            [
                'user_id' => 3,
                'username' => 'user_no.3',
                'email' => 'user_no.3@gmail.com',
                'password_hash' => bcrypt('user_no.3'),
                'phone' => '0868123987',
                'role' => 'user',
                'created_at' => now(),
            ],
            [
                'user_id' => 4,
                'username' => 'user_no.4',
                'email' => 'user_no.4@gmail.com',
                'password_hash' => bcrypt('user_no.4'),
                'phone' => '0707456123',
                'role' => 'user',
                'created_at' => now(),
            ],
            [
                'user_id' => 5,
                'username' => 'user_no.5',
                'email' => 'user_no.5@gmail.com',
                'password_hash' => bcrypt('user_no.5'),
                'phone' => '0333987654',
                'role' => 'user',
                'created_at' => now(),
            ],
        ]);
        DB::statement("SELECT setval('users_user_id_seq', (SELECT MAX(user_id) FROM users))");
    }
}
