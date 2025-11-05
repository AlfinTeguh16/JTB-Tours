<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Faker\Factory as Faker;
use Carbon\Carbon;

class InitialSeeder extends Seeder
{
    protected function getColumnInfo(string $table, string $column)
    {
        if (! Schema::hasTable($table)) return null;
        $pdo = DB::getPdo();
        $quoted = $pdo->quote($column);
        $sql = "SHOW COLUMNS FROM `{$table}` LIKE {$quoted}";
        $res = DB::select($sql);
        if (empty($res)) return null;
        return (array) $res[0];
    }

    protected function columnIsNullable(string $table, string $column): bool
    {
        $info = $this->getColumnInfo($table, $column);
        if (! $info) return false;
        return isset($info['Null']) && strtoupper($info['Null']) === 'YES';
    }

    protected function defaultForColumn(string $table, string $column)
    {
        $info = $this->getColumnInfo($table, $column);
        if (! $info) return null;

        if (isset($info['Type'])) {
            $type = strtolower($info['Type']);
            if (str_contains($type, 'int') || str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
                return 0;
            }
            if (str_contains($type, 'timestamp') || str_contains($type, 'datetime') || str_contains($type, 'date')) {
                return Carbon::now()->toDateTimeString();
            }
            return '';
        }

        return '';
    }

    protected function filterToTableColumns(string $table, array $data): array
    {
        if (! Schema::hasTable($table)) return [];
        $cols = Schema::getColumnListing($table);
        $filtered = array_intersect_key($data, array_flip($cols));

        foreach ($filtered as $col => $val) {
            if (is_null($val) && ! $this->columnIsNullable($table, $col)) {
                $filtered[$col] = $this->defaultForColumn($table, $col);
            }
        }
        return $filtered;
    }

    public function run()
    {
        $faker = Faker::create();

        // FORCE all timestamps/dates to year 2025
        $seedYear = 2025;
        // "now" anchored in 2025 (keperluan created_at/updated_at default)
        $now = Carbon::now()->setYear($seedYear);

        // helper: random datetime inside year 2025 with hour between given bounds
        $randomInYear = function(int $year = 2025, int $hourMin = 6, int $hourMax = 20) use ($faker) {
            $month = rand(1, 12);
            $day = rand(1, 28); // safe day to avoid month overflow
            $hour = rand($hourMin, $hourMax);
            $minute = [0,15,30,45][array_rand([0,1,2,3])];
            return Carbon::create($year, $month, $day, $hour, $minute, 0);
        };

        $this->command->info('Starting InitialSeeder (timestamps forced to ' . $seedYear . ')...');
        DB::beginTransaction();

        try {
            // USERS
            if (class_exists(\App\Models\User::class) && Schema::hasTable('users')) {
                $this->command->info('Seeding users...');

                // gunakan tanggal join_date dalam tahun 2025
                $usersToEnsure = [
                    [
                        'name' => 'Super Admin',
                        'email' => 'superadmin@example.com',
                        'phone' => '081200000000',
                        'password' => Hash::make('password'),
                        'role' => 'super_admin',
                        'join_date' => $randomInYear($seedYear, 8, 12)->toDateString(),
                        'monthly_work_limit' => null,
                        'used_hours' => 0.0,
                        'status' => 'offline',
                        'remember_token' => Str::random(10),
                    ],
                    [
                        'name' => 'Admin User',
                        'email' => 'admin@example.com',
                        'phone' => '081211111111',
                        'password' => Hash::make('password'),
                        'role' => 'admin',
                        'join_date' => $randomInYear($seedYear, 8, 12)->toDateString(),
                        'monthly_work_limit' => null,
                        'used_hours' => 0.0,
                        'status' => 'offline',
                        'remember_token' => Str::random(10),
                    ],
                    [
                        'name' => 'Staff User',
                        'email' => 'staff@example.com',
                        'phone' => '081222222222',
                        'password' => Hash::make('password'),
                        'role' => 'staff',
                        'join_date' => $randomInYear($seedYear, 8, 12)->toDateString(),
                        'monthly_work_limit' => null,
                        'used_hours' => 0.0,
                        'status' => 'offline',
                        'remember_token' => Str::random(10),
                    ],
                ];

                foreach ($usersToEnsure as $u) {
                    $uFiltered = $this->filterToTableColumns('users', $u);
                    \App\Models\User::updateOrCreate(['email' => $u['email']], $uFiltered);
                }

                // DRIVERS
                for ($i = 1; $i <= 5; $i++) {
                    $u = [
                        'name' => "Driver {$i}",
                        'email' => "driver{$i}@example.com",
                        'phone' => '0812' . $faker->numerify('########'),
                        'password' => Hash::make('password'),
                        'role' => 'driver',
                        // join_date di 2025
                        'join_date' => $randomInYear($seedYear, 6, 18)->toDateString(),
                        'monthly_work_limit' => 200,
                        // used_hours as float now
                        'used_hours' => (float) $faker->randomFloat(2, 0, 40),
                        'status' => (rand(0,1) ? 'online' : 'offline'),
                        'remember_token' => Str::random(10),
                    ];
                    $uFiltered = $this->filterToTableColumns('users', $u);
                    \App\Models\User::updateOrCreate(['email' => $u['email']], $uFiltered);
                }

                // GUIDES
                for ($i = 1; $i <= 3; $i++) {
                    $u = [
                        'name' => "Guide {$i}",
                        'email' => "guide{$i}@example.com",
                        'phone' => '0812' . $faker->numerify('########'),
                        'password' => Hash::make('password'),
                        'role' => 'guide',
                        'join_date' => $randomInYear($seedYear, 6, 18)->toDateString(),
                        'monthly_work_limit' => 160,
                        // used_hours as float now
                        'used_hours' => (float) $faker->randomFloat(2, 0, 20),
                        'status' => (rand(0,1) ? 'online' : 'offline'),
                        'remember_token' => Str::random(10),
                    ];
                    $uFiltered = $this->filterToTableColumns('users', $u);
                    \App\Models\User::updateOrCreate(['email' => $u['email']], $uFiltered);
                }
            }

            // PRODUCTS
            if (Schema::hasTable('products')) {
                $this->command->info('Seeding products...');
                $products = [
                    ['name' => 'Hotel Transfer', 'capacity' => 4, 'description' => 'Transfer from/to hotels.'],
                    ['name' => 'Check-in Assistance', 'capacity' => 2, 'description' => 'Help guest with check-in process.'],
                    ['name' => 'Tour Travel - Half Day', 'capacity' => 30, 'description' => 'Half day tour package.'],
                    ['name' => 'Tour Travel - Full Day', 'capacity' => 30, 'description' => 'Full day tour package.'],
                ];
                foreach ($products as $p) {
                    \App\Models\Product::updateOrCreate(['name' => $p['name']], $p);
                }
            }

            // VEHICLES (tidak mengubah kolom year kendaraan karena kamu minta timestamp saja)
            if (Schema::hasTable('vehicles')) {
                $this->command->info('Seeding vehicles...');
                $vehicles = [
                    ['brand'=>'Toyota', 'type'=>'Avanza', 'plate_number'=>'DK 1111 AA', 'color'=>'White', 'status'=>'available', 'year'=>2019, 'capacity'=>6],
                    ['brand'=>'Toyota', 'type'=>'Hiace', 'plate_number'=>'DK 2222 BB', 'color'=>'Silver', 'status'=>'available', 'year'=>2018, 'capacity'=>14],
                    ['brand'=>'Mercedes', 'type'=>'Bus', 'plate_number'=>'DK 3333 CC', 'color'=>'Blue', 'status'=>'maintenance', 'year'=>2016, 'capacity'=>30],
                    ['brand'=>'Honda', 'type'=>'CRV', 'plate_number'=>'DK 4444 DD', 'color'=>'Black', 'status'=>'in_use', 'year'=>2020, 'capacity'=>4],
                ];
                foreach ($vehicles as $v) {
                    \App\Models\Vehicle::updateOrCreate(['plate_number' => $v['plate_number']], $v);
                }
            }

            // ORDERS
            if (Schema::hasTable('orders')) {
                $this->command->info('Seeding orders...');
                $creator = \App\Models\User::whereIn('role', ['admin','super_admin'])->first();
                $productIds = \App\Models\Product::pluck('id')->toArray();

                for ($i = 1; $i <= 15; $i++) {
                    // pickup & arrival di tahun 2025
                    $pickup = $randomInYear($seedYear, 6, 20);
                    $arrival = (clone $pickup)->addMinutes(rand(30, 480));

                    $order = [
                        'customer_name' => $faker->name(),
                        'email' => $faker->optional()->safeEmail(),
                        'phone' => $faker->phoneNumber(),
                        'pickup_time' => $pickup->toDateTimeString(),
                        'arrival_time' => $arrival->toDateTimeString(),
                        'estimated_duration_minutes' => $arrival->diffInMinutes($pickup),
                        'passengers' => rand(1,10),
                        'pickup_location' => $faker->address(),
                        'destination' => $faker->city(),
                        'product_id' => $faker->randomElement($productIds),
                        'adults' => rand(1,6),
                        'children' => rand(0,3),
                        'babies' => rand(0,2),
                        'vehicle_count' => rand(1,2),
                        'note' => $faker->optional()->sentence(),
                        'created_by' => $creator?->id,
                        'status' => ['pending','assigned','completed'][array_rand(['pending','assigned','completed'])],
                    ];
                    \App\Models\Order::create($order);
                }
            }

            // WORK SCHEDULES
            if (Schema::hasTable('work_schedules')) {
                $this->command->info('Seeding work schedules...');
                $users = \App\Models\User::whereIn('role', ['driver','guide'])->get();
                foreach ($users as $u) {
                    \App\Models\WorkSchedule::updateOrCreate(
                        ['user_id' => $u->id, 'month' => $now->month, 'year' => $seedYear],
                        [
                            'total_hours' => $u->monthly_work_limit ?? 200,
                            'used_hours' => (float) ($u->used_hours ?? 0.0)
                        ]
                    );
                }
            }

            // ASSIGNMENTS
            if (Schema::hasTable('assignments')) {
                $this->command->info('Seeding assignments...');
                $orders = \App\Models\Order::inRandomOrder()->take(10)->get();
                $drivers = \App\Models\User::where('role', 'driver')->pluck('id')->toArray();
                $guides = \App\Models\User::where('role', 'guide')->pluck('id')->toArray();
                $assigners = \App\Models\User::whereIn('role', ['admin','super_admin'])->pluck('id')->toArray();

                foreach ($orders as $order) {
                    $statusOptions = ['pending', 'accepted', 'completed', 'declined'];
                    $status = $statusOptions[array_rand($statusOptions)];

                    // pastikan assignedAt juga di tahun 2025
                    $assignedAt = Carbon::parse($order->pickup_time)->subHours(rand(1, 12))->setYear($seedYear);
                    $workstart = null;
                    $workend = null;

                    if (in_array($status, ['accepted', 'completed'])) {
                        $workstart = Carbon::parse($order->pickup_time)->subMinutes(rand(15, 45))->setYear($seedYear);
                    }

                    if ($status === 'completed' && $workstart) {
                        $duration = rand(30, 180);
                        $workend = (clone $workstart)->addMinutes($duration)->setYear($seedYear);
                    }

                    $data = [
                        'order_id' => $order->id,
                        'driver_id' => $faker->randomElement($drivers),
                        'guide_id' => $faker->optional()->randomElement($guides),
                        'assigned_by' => $faker->randomElement($assigners),
                        'status' => $status,
                        'workstart' => $workstart?->format('H:i:s'),
                        'workend' => $workend?->format('H:i:s'),
                        'assigned_at' => $assignedAt->toDateTimeString(),
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];

                    $assignment = \App\Models\Assignment::create($data);

                    // Update used_hours jika completed
                    if ($status === 'completed' && $workstart && $workend) {
                        $minutes = $workend->diffInMinutes($workstart);
                        $hoursToAdd = round($minutes / 60, 2);
                        $ws = \App\Models\WorkSchedule::where('user_id', $assignment->driver_id)
                            ->where('month', $now->month)
                            ->where('year', $seedYear)
                            ->first();
                        if ($ws) {
                            $newUsed = $ws->used_hours + $hoursToAdd;
                            // simpan sebagai float dengan 2 desimal
                            $ws->used_hours = round($newUsed, 2);
                            $ws->save();
                        }
                    }
                }
            }

            DB::commit();
            $this->command->info('InitialSeeder completed successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('InitialSeeder failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
