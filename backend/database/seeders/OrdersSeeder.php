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
        $year = 2026;
        $months = [5, 6]; // May and June
        $minPerMonthBooking = 1000;
        $maxPerMonthBooking = 1500;
        $minPerMonthDelivery = 1000;
        $maxPerMonthDelivery = 1500;

        // 1) Ensure we have 20 users (including admin in DB already)
        $existingUsers = DB::table('users')->orderBy('user_id')->get();
        $existingCount = $existingUsers->count();

        // Determine starting index for phone prefix based on existing rows
        $startIndex = $existingCount > 0 ? $existingCount + 1 : 1;

        // Add users until we have admin + 20 non-admins (i.e., total users = 1 + 20 = 21)
        $targetTotalUsers = 21; // admin + 20
        $toCreate = max(0, $targetTotalUsers - $existingCount);

        $newUserIds = [];

        // Helper to format tele_number according to your requested pattern:
        // - For n < 10: '0n23456789' (e.g. 9 -> 0923456789)
        // - For n >= 10: '0NN3456789' where NN is two-digit n (e.g. 10 -> 0103456789, 21 -> 0213456789)
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

            if ($idx == 12) {
                continue; // né số 12, đã dùng riêng cho admin
            }

            if ($idx == 22) {
                continue;
            }

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

        // Refresh users list (exclude admin)
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

        // helper closures
        $genId = function ($prefix = 'ID') {
            return strtoupper($prefix . Str::random(12));
        };

        // Keep in-memory map of bookings per table/date to avoid overlaps during this seeding run
        $occupied = [];

        // Function to check overlap using DB for safety
        $hasOverlap = function ($tableNumber, $date, $start, $end) {
            $rows = DB::table('booking_tables')
                ->where('table_number', $tableNumber)
                ->where('booking_date', $date)
                ->where('booking_status', '<>', 'cancelled')
                ->whereRaw("NOT (end_time <= ? OR start_time >= ?)", [$start, $end])
                ->exists();
            return $rows;
        };

        // Create orders per month
        foreach ($months as $month) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            // Bookings
            // Bookings
            $bookingCount = rand($minPerMonthBooking, $maxPerMonthBooking);
            $this->command->info("Seeding {$bookingCount} bookings for {$year}-{$month}");

            for ($i = 0; $i < $bookingCount; $i++) {
                // pick a user
                $userId = $users[array_rand($users)];

                // Generate created_at and booking_date within the month
                $bookingDate = now()->setDate($year, $month, rand(1, $daysInMonth))->startOfDay();
                // pick time slot start between 10:00 and 20:00 in 30-min steps
                $startHour = rand(10, 20);
                $startMin = (rand(0, 1) * 30);
                $startTime = sprintf('%02d:%02d:00', $startHour, $startMin);
                $durationMins = [60, 90, 120][array_rand([0,1,2])];
                $endTimestamp = strtotime($bookingDate->toDateString() . ' ' . $startTime) + $durationMins * 60;
                $endTime = date('H:i:s', $endTimestamp);

                // choose a table that is free at that slot
                $tries = 0;
                do {
                    $tableNumber = $tableNumbers[array_rand($tableNumbers)];
                    $inMemory = $occupied[$tableNumber][$bookingDate->toDateString()] ?? [];
                    $conflict = false;
                    foreach ($inMemory as $range) {
                        if (!($endTime <= $range[0] || $startTime >= $range[1])) { $conflict = true; break; }
                    }
                    // also check DB existing bookings
                    if (!$conflict && $hasOverlap($tableNumber, $bookingDate->toDateString(), $startTime, $endTime)) {
                        $conflict = true;
                    }
                    $tries++;
                    if ($tries > 10) break;
                } while ($conflict);

                if ($tries > 10 && $conflict) {
                    // couldn't find free slot quickly; skip this booking to avoid infinite loop
                    continue;
                }

                // register occupied
                $occupied[$tableNumber][$bookingDate->toDateString()][] = [$startTime, $endTime];

                // Create order
                $orderId = $genId('ORD');
                $createdAt = $bookingDate->addSeconds(rand(0, 3600));
                DB::table('orders')->insert([
                    'order_id' => $orderId,
                    'order_stt' => null,
                    'user_id' => $userId,
                    'order_type' => 'booking_table',
                    'subtotal_price' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Add random items
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

                // update order subtotal
                DB::table('orders')->where('order_id', $orderId)->update(['subtotal_price' => $subtotal]);

                // Create bill (paid)
                $billId = $genId('BIL');
                DB::table('bills')->insert([
                    'bill_id' => $billId,
                    'order_id' => $orderId,
                    'total_price' => $subtotal,
                    'user_id' => $userId,
                    'vnp_txn_ref' => null,
                    'payment_method' => 'CASH',
                    'created_at' => $createdAt,
                ]);

                // Create booking_table record (completed & paid)
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

            // Deliveries
            $deliveryCount = rand($minPerMonthDelivery, $maxPerMonthDelivery);
            $this->command->info("Seeding {$deliveryCount} deliveries for {$year}-{$month}");

            for ($i = 0; $i < $deliveryCount; $i++) {
                $userId = $users[array_rand($users)];
                $day = rand(1, $daysInMonth);
                $createdAt = now()->setDate($year, $month, $day)->addSeconds(rand(0, 86400));

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
                DB::table('bills')->insert([
                    'bill_id' => $billId,
                    'order_id' => $orderId,
                    'total_price' => $subtotal,
                    'user_id' => $userId,
                    'vnp_txn_ref' => null,
                    'payment_method' => 'CASH',
                    'created_at' => $createdAt,
                ]);

                // delivery record
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
                    'approved_at' => $isCompleted ? $createdAt->addMinutes(rand(10, 120)) : null,
                    'delivered_at' => $isCompleted ? $createdAt->addMinutes(rand(121, 360)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        $this->command->info('Seeding orders completed.');
    }
}
