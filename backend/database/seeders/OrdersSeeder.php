<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        // Configuration
        $startYear = 2025;
        $startMonth = 1;

        $now = now();
        $endYear = (int) $now->year;
        $endMonth = (int) $now->month;
        $currentDay = (int) $now->day;

        $minPerMonth = 350;
        $maxPerMonth = 450;

        // 1) Ensure we have 20 users (including admin in DB already)
        $existingUsers = DB::table('users')->orderBy('user_id')->get();
        $existingCount = $existingUsers->count();

        $startIndex = $existingCount > 0 ? $existingCount + 1 : 1;

        $targetTotalUsers = 21; // admin + 20
        $toCreate = max(0, $targetTotalUsers - $existingCount);

        $newUserIds = [];

        $formatTele = function ($n) {
            if ($n < 10) {
                return sprintf('0%d23456789', $n);
            }
            return sprintf('0%02d3456789', $n);
        };

        $i = 0;
        $created = 0;
        while ($created < $toCreate) {
            $idx = $startIndex + $i;
            $i++;

            if ($idx == 12) continue;
            if ($idx == 22) continue;

            $username = 'user' . ($idx - 1);
            $tele = $formatTele($idx);

            $id = DB::table('users')->insertGetId([
                'username' => $username,
                'email' => $username . '@example.com',
                'password_hash' => Hash::make('password'),
                'tele_number' => $tele,
                'role' => 'user',
                'points' => 0,
                'membership' => 'bronze',
                'created_at' => now(),
            ], 'user_id');

            $newUserIds[] = $id;
            $created++;
        }

        $users = DB::table('users')->where('role', '<>', 'admin')->pluck('user_id')->toArray();
        if (count($users) < 1) {
            $this->command->error('No non-admin users available to seed orders.');
            return;
        }

        $dishIds = DB::table('dishes')->pluck('dish_id')->toArray();
        if (empty($dishIds)) {
            $this->command->error('No dishes found. Run dish seeder first.');
            return;
        }

        $tableNumbers = DB::table('restaurant_tables')->pluck('table_number')->toArray();
        if (empty($tableNumbers)) {
            $this->command->error('No restaurant tables found.');
            return;
        }

        $genId = function ($prefix = 'ID') {
            return strtoupper($prefix . Str::random(12));
        };

        $occupied = [];

        $hasOverlap = function ($tableNumber, $date, $start, $end) {
            return DB::table('booking_tables')
                ->where('table_number', $tableNumber)
                ->where('booking_date', $date)
                ->where('booking_status', '<>', 'cancelled')
                ->whereRaw("NOT (end_time <= ? OR start_time >= ?)", [$start, $end])
                ->exists();
        };

        // 2) Build danh sách các (year, month) từ startYear/startMonth đến endYear/endMonth
        $monthList = [];
        $y = $startYear;
        $m = $startMonth;
        while ($y < $endYear || ($y == $endYear && $m <= $endMonth)) {
            $monthList[] = [$y, $m];
            $m++;
            if ($m > 12) { $m = 1; $y++; }
        }

        foreach ($monthList as [$year, $month]) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $isCurrentMonth = ($year == $endYear && $month == $endMonth);
            $maxCreatedDay = $isCurrentMonth ? $currentDay : $daysInMonth;

            // đảm bảo luôn còn ít nhất 1 ngày sau created_at trong cùng tháng cho booking_date
            $maxCreatedDayForBooking = max(1, min($maxCreatedDay, $daysInMonth - 1));

            // ============ BOOKINGS ============
            $bookingCount = rand($minPerMonth, $maxPerMonth);
            $this->command->info("Seeding {$bookingCount} bookings for {$year}-{$month}");

            for ($i = 0; $i < $bookingCount; $i++) {
                $userId = $users[array_rand($users)];

                // created_at: ngày trong khoảng cho phép, giờ trong khung 7h-22h (đến 21:59:59)
                $createdDay = rand(1, $maxCreatedDayForBooking);
                $createdHour = rand(7, 21);
                $createdMinute = rand(0, 59);
                $createdSecond = rand(0, 59);
                $createdAt = now()->setDate($year, $month, $createdDay)
                    ->setTime($createdHour, $createdMinute, $createdSecond);

                // booking_date: cùng tháng, sau created_at (ngày lớn hơn), trong tháng đó
                $bookingDay = rand($createdDay + 1, $daysInMonth);
                $bookingDate = now()->setDate($year, $month, $bookingDay)->startOfDay();

                // giờ đặt bàn trong khung 7h-20h30 (để đặt tối đa 90' vẫn kết thúc trước/đúng 22h)
                $startHour = rand(7, 20);
                $startMin = (rand(0, 1) * 30);
                $startTime = sprintf('%02d:%02d:00', $startHour, $startMin);
                $durationMins = [30, 60, 90][array_rand([0, 1, 2])];
                $endTimestamp = strtotime($bookingDate->toDateString() . ' ' . $startTime) + $durationMins * 60;
                $endTime = date('H:i:s', $endTimestamp);

                // chọn bàn còn trống
                $tries = 0;
                $conflict = true;
                $tableNumber = null;
                do {
                    $tableNumber = $tableNumbers[array_rand($tableNumbers)];
                    $inMemory = $occupied[$tableNumber][$bookingDate->toDateString()] ?? [];
                    $conflict = false;
                    foreach ($inMemory as $range) {
                        if (!($endTime <= $range[0] || $startTime >= $range[1])) { $conflict = true; break; }
                    }
                    if (!$conflict && $hasOverlap($tableNumber, $bookingDate->toDateString(), $startTime, $endTime)) {
                        $conflict = true;
                    }
                    $tries++;
                    if ($tries > 10) break;
                } while ($conflict);

                if ($tries > 10 && $conflict) {
                    continue;
                }

                $occupied[$tableNumber][$bookingDate->toDateString()][] = [$startTime, $endTime];

                $orderId = $genId('ORD');
                DB::table('orders')->insert([
                    'order_id' => $orderId,
                    'order_stt' => null,
                    'user_id' => $userId,
                    'order_type' => 'booking_table',
                    'subtotal_price' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $itemDishes = (array) array_rand(array_flip($dishIds), rand(1, 5));
                $subtotal = 0;
                foreach ($itemDishes as $dishId) {
                    $qty = rand(1, 4);
                    $unit = DB::table('dishes')->where('dish_id', $dishId)->value('price') ?? 30000;
                    DB::table('order_items')->insert([
                        'order_id' => $orderId,
                        'dish_id' => $dishId,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                    $subtotal += $qty * $unit;
                }

                DB::table('orders')->where('order_id', $orderId)->update(['subtotal_price' => $subtotal]);

                $billId = $genId('BIL');
                $paymentMethod = rand(0, 1) ? 'Points' : 'VNPay';
                DB::table('bills')->insert([
                    'bill_id' => $billId,
                    'order_id' => $orderId,
                    'total_price' => $subtotal,
                    'user_id' => $userId,
                    'vnp_txn_ref' => null,
                    'payment_method' => $paymentMethod,
                    'created_at' => $createdAt,
                ]);

                $bookingId = $genId('BKG');
                DB::table('booking_tables')->insert([
                    'booking_id' => $bookingId,
                    'booking_stt' => null,
                    'order_id' => $orderId,
                    'table_number' => $tableNumber,
                    'booking_date' => $bookingDate->toDateString(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'B_payment_status' => 'paid',
                    'booking_status' => 'completed',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            // ============ DELIVERIES ============
            $deliveryCount = rand($minPerMonth, $maxPerMonth);
            $this->command->info("Seeding {$deliveryCount} deliveries for {$year}-{$month}");

            for ($i = 0; $i < $deliveryCount; $i++) {
                $userId = $users[array_rand($users)];

                $day = rand(1, $maxCreatedDay);
                $hour = rand(7, 21);
                $minute = rand(0, 59);
                $second = rand(0, 59);
                $createdAt = now()->setDate($year, $month, $day)->setTime($hour, $minute, $second);

                $orderId = $genId('ORD');
                DB::table('orders')->insert([
                    'order_id' => $orderId,
                    'order_stt' => null,
                    'user_id' => $userId,
                    'order_type' => 'delivery',
                    'subtotal_price' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $itemDishes = (array) array_rand(array_flip($dishIds), rand(1, 6));
                $subtotal = 0;
                foreach ($itemDishes as $dishId) {
                    $qty = rand(1, 5);
                    $unit = DB::table('dishes')->where('dish_id', $dishId)->value('price') ?? 30000;
                    DB::table('order_items')->insert([
                        'order_id' => $orderId,
                        'dish_id' => $dishId,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                    $subtotal += $qty * $unit;
                }

                DB::table('orders')->where('order_id', $orderId)->update(['subtotal_price' => $subtotal]);

                $billId = $genId('BIL');
                $paymentMethod = rand(0, 1) ? 'Points' : 'VNPay';
                DB::table('bills')->insert([
                    'bill_id' => $billId,
                    'order_id' => $orderId,
                    'total_price' => $subtotal,
                    'user_id' => $userId,
                    'vnp_txn_ref' => null,
                    'payment_method' => $paymentMethod,
                    'created_at' => $createdAt,
                ]);

                $deliveryId = $genId('DLY');
                $isCompleted = (bool) rand(0, 1);
                $deliveryStatus = $isCompleted ? 'completed' : 'cancelled';
                $dPayment = $isCompleted ? 'paid' : 'refunded';

                DB::table('deliveries')->insert([
                    'delivery_id' => $deliveryId,
                    'order_id' => $orderId,
                    'address' => 'Auto seed address ' . rand(100, 999),
                    'D_payment_status' => $dPayment,
                    'delivery_status' => $deliveryStatus,
                    'approved_at' => $isCompleted ? $createdAt->copy()->addMinutes(rand(10, 120)) : null,
                    'delivered_at' => $isCompleted ? $createdAt->copy()->addMinutes(rand(121, 360)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        $this->command->info('Seeding orders completed.');
    }
}