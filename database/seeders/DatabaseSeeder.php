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
            [
                'user_id' => 6,
                'username' => 'user_no.6',
                'email' => 'user_no.6@gmail.com',
                'password_hash' => bcrypt('user_no.6'),
                'phone' => '0911222333',
                'role' => 'user',
                'created_at' => now(),
            ],
            [
                'user_id' => 7,
                'username' => 'user_no.7',
                'email' => 'user_no.7@gmail.com',
                'password_hash' => bcrypt('user_no.7'),
                'phone' => '0922333444',
                'role' => 'user',
                'created_at' => now(),
            ],
            [
                'user_id' => 8,
                'username' => 'user_no.8',
                'email' => 'user_no.8@gmail.com',
                'password_hash' => bcrypt('user_no.8'),
                'phone' => '0933444555',
                'role' => 'user',
                'created_at' => now(),
            ],
            [
                'user_id' => 9,
                'username' => 'user_no.9',
                'email' => 'user_no.9@gmail.com',
                'password_hash' => bcrypt('user_no.9'),
                'phone' => '0944555666',
                'role' => 'user',
                'created_at' => now(),
            ],
            [
                'user_id' => 10,
                'username' => 'user_no.10',
                'email' => 'user_no.10@gmail.com',
                'password_hash' => bcrypt('user_no.10'),
                'phone' => '0955666777',
                'role' => 'user',
                'created_at' => now(),
            ],
        ]);
        DB::statement("SELECT setval('users_user_id_seq', (SELECT MAX(user_id) FROM users))");

        // Seed Bills, BillDetails, and BookingTables
        $users = DB::table('users')->where('role', 'user')->get();
        $dishes = DB::table('dishes')->limit(3)->get();
        $today = now();
        $onlineOrderCounter = 1; // Counter for 18 online orders on Today

        foreach ($users as $user) {
            $uId = $user->user_id;
            $username = $user->username;
            $futureBookingDate = $today->copy()->addDays($uId);

            // Determine payment method
            $paymentMethod = 'Tiền mặt';
            if (in_array($uId, [2, 5, 8]))
                $paymentMethod = 'MoMo';
            elseif (in_array($uId, [3, 6, 9]))
                $paymentMethod = 'VNPay';

            for ($i = 1; $i <= 4; $i++) {
                // ID1: online + paid, ID2: booking + paid, ID3: online + unpaid, ID4: booking + unpaid
                $isOnline = ($i == 1 || $i == 3);
                $isPaid = ($i == 1 || $i == 2);

                $type = $isOnline ? 'mang-ve' : 'dat-ban';
                $status = $isPaid ? 'completed' : 'pending';

                // For online orders, booking_date is ALWAYS today. 
                // For table bookings, booking_date is today + uId.
                $currentBookingDate = $isOnline ? $today : $futureBookingDate;
                $dateSuffix = $currentBookingDate->format('dmY');

                // Determine order_in_day
                if ($isOnline) {
                    $orderInDay = $onlineOrderCounter++;
                } else {
                    // Each user has a unique date for booking, so they occupy order 1 and 2 of that date
                    $orderInDay = ($i == 2) ? 1 : 2;
                }

                $billCode = str_pad($orderInDay, 3, '0', STR_PAD_LEFT) . $dateSuffix;

                $billId = DB::table('bills')->insertGetId([
                    'bill_code' => $billCode,
                    'order_in_day' => $orderInDay,
                    'user_id' => $uId,
                    'customer_name' => $username,
                    'total_amount' => 120000, // 4 món x 30.000đ = 120.000đ
                    'order_type' => $type,
                    'address' => 'aaa',
                    'table_number' => ($type === 'dat-ban') ? ($isPaid ? '1, 26, 46' : '2, 27, 47') : null,
                    'booking_date' => $currentBookingDate->format('Y-m-d'),
                    'arrival_time' => ($type === 'dat-ban') ? '07:00' : null,
                    'finish_time' => ($type === 'dat-ban') ? '08:00' : null,
                    'status' => $status,
                    'is_paid' => $isPaid,
                    'payment_method' => $paymentMethod,
                    'paid_at' => $isPaid ? $today : null,
                    'created_at' => $today,
                ]);

                // Determine dish range based on user groups
                $dishIds = [];
                if ($uId >= 2 && $uId <= 4)
                    $dishIds = [1, 2, 3, 4];
                elseif ($uId >= 5 && $uId <= 7)
                    $dishIds = [5, 6, 7, 8];
                elseif ($uId >= 8 && $uId <= 10)
                    $dishIds = [9, 10, 11, 12];

                // Seed Bill Details based on determined dish range
                foreach ($dishIds as $dId) {
                    DB::table('bill_details')->insert([
                        'bill_id' => $billId,
                        'dish_id' => $dId,
                        'quantity' => 1,
                        'price_at_time' => 30000, // Giá cố định 30k như trong seeder
                        'note' => 'Không có',
                    ]);
                }

                // Seed Booking Tables if type is dat-ban
                if ($type === 'dat-ban') {
                    $tableNums = $isPaid ? [1, 26, 46] : [2, 27, 47];
                    foreach ($tableNums as $num) {
                        DB::table('booking_tables')->insert([
                            'bill_id' => $billId,
                            'table_number' => $num,
                            'start_time' => $currentBookingDate->format('Y-m-d') . ' 07:00:00',
                            'end_time' => $currentBookingDate->format('Y-m-d') . ' 08:00:00',
                        ]);
                    }
                }
            }
        }
    }
}
