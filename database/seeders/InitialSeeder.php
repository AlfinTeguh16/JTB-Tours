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
    /**
     * Return column info for a given table.column using SHOW COLUMNS.
     * Uses PDO quote to avoid binding issues on SHOW statements.
     */
    protected function getColumnInfo(string $table, string $column)
    {
        // ensure table exists
        if (! Schema::hasTable($table)) {
            return null;
        }

        // quote column safely for inclusion in SQL
        $pdo = DB::getPdo();
        $quoted = $pdo->quote($column); // yields e.g. 'monthly_work_limit'

        // Build and run SHOW COLUMNS query safely
        $sql = "SHOW COLUMNS FROM `{$table}` LIKE {$quoted}";
        $res = DB::select($sql);

        if (empty($res)) return null;
        return (array) $res[0];
    }

    /**
     * Check if given column is nullable.
     */
    protected function columnIsNullable(string $table, string $column): bool
    {
        $info = $this->getColumnInfo($table, $column);
        if (! $info) return false;
        return isset($info['Null']) && strtoupper($info['Null']) === 'YES';
    }

    /**
     * Try to guess sensible default for a column that is NOT NULL but data is null.
     */
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

    /**
     * Filter data to only include columns that exist in table and fill NOT NULL columns with defaults when needed.
     */
    protected function filterToTableColumns(string $table, array $data): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

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
        $now = Carbon::now();

        $this->command->info('Starting InitialSeeder...');

        DB::beginTransaction();
        try {
            // USERS
            if (class_exists(\App\Models\User::class) && Schema::hasTable('users')) {
                $this->command->info('Seeding users...');

                $usersToEnsure = [
                    [
                        'name' => 'Super Admin',
                        'email' => 'superadmin@example.com',
                        'phone' => '081200000000',
                        'password' => Hash::make('password'),
                        'role' => 'super_admin',
                        'join_date' => $now->subYears(2)->toDateString(),
                        'monthly_work_limit' => null,
                        'used_hours' => 0,
                        'status' => 'offline',
                        'remember_token' => Str::random(10),
                    ],
                    [
                        'name' => 'Admin User',
                        'email' => 'admin@example.com',
                        'phone' => '081211111111',
                        'password' => Hash::make('password'),
                        'role' => 'admin',
                        'join_date' => $now->subYear()->toDateString(),
                        'monthly_work_limit' => null,
                        'used_hours' => 0,
                        'status' => 'offline',
                        'remember_token' => Str::random(10),
                    ],
                    [
                        'name' => 'Staff User',
                        'email' => 'staff@example.com',
                        'phone' => '081222222222',
                        'password' => Hash::make('password'),
                        'role' => 'staff',
                        'join_date' => $now->subMonths(6)->toDateString(),
                        'monthly_work_limit' => null,
                        'used_hours' => 0,
                        'status' => 'offline',
                        'remember_token' => Str::random(10),
                    ],
                ];

                foreach ($usersToEnsure as $u) {
                    $uFiltered = $this->filterToTableColumns('users', $u);
                    \App\Models\User::updateOrCreate(['email' => $u['email']], $uFiltered);
                }

                // Drivers
                $numDrivers = 5;
                for ($i = 1; $i <= $numDrivers; $i++) {
                    $u = [
                        'name' => "Driver {$i}",
                        'email' => "driver{$i}@example.com",
                        'phone' => '0812' . $faker->numerify('########'),
                        'password' => Hash::make('password'),
                        'role' => 'driver',
                        'join_date' => $now->subMonths(rand(1,12))->toDateString(),
                        'monthly_work_limit' => 200,
                        'used_hours' => rand(0,40),
                        'status' => (rand(0,1) ? 'online' : 'offline'),
                        'remember_token' => Str::random(10),
                    ];
                    $uFiltered = $this->filterToTableColumns('users', $u);
                    \App\Models\User::updateOrCreate(['email' => $u['email']], $uFiltered);
                }

                // Guides
                $numGuides = 3;
                for ($i = 1; $i <= $numGuides; $i++) {
                    $u = [
                        'name' => "Guide {$i}",
                        'email' => "guide{$i}@example.com",
                        'phone' => '0812' . $faker->numerify('########'),
                        'password' => Hash::make('password'),
                        'role' => 'guide',
                        'join_date' => $now->subMonths(rand(1,12))->toDateString(),
                        'monthly_work_limit' => 160,
                        'used_hours' => rand(0,20),
                        'status' => (rand(0,1) ? 'online' : 'offline'),
                        'remember_token' => Str::random(10),
                    ];
                    $uFiltered = $this->filterToTableColumns('users', $u);
                    \App\Models\User::updateOrCreate(['email' => $u['email']], $uFiltered);
                }
            } else {
                $this->command->warn('User model or users table not found — skipping user seeding.');
            }

            // PRODUCTS
            if (class_exists(\App\Models\Product::class) && Schema::hasTable('products')) {
                $this->command->info('Seeding products...');
                $products = [
                    ['name' => 'Hotel Transfer', 'capacity' => 4, 'description' => 'Transfer from/to hotels.'],
                    ['name' => 'Check-in Assistance', 'capacity' => 2, 'description' => 'Help guest with check-in process.'],
                    ['name' => 'Tour Travel - Half Day', 'capacity' => 30, 'description' => 'Half day tour package with guide.'],
                    ['name' => 'Tour Travel - Full Day', 'capacity' => 30, 'description' => 'Full day tour package with guide.'],
                ];
                foreach ($products as $p) {
                    $pFiltered = $this->filterToTableColumns('products', $p);
                    \App\Models\Product::updateOrCreate(['name' => $p['name']], $pFiltered);
                }
            } else {
                $this->command->warn('Product model or products table not found — skipping product seeding.');
            }

            // VEHICLES
            if (class_exists(\App\Models\Vehicle::class) && Schema::hasTable('vehicles')) {
                $this->command->info('Seeding vehicles...');
                $vehicles = [
                    ['brand'=>'Toyota', 'type'=>'Avanza', 'plate_number'=>'DK 1111 AA', 'color'=>'White', 'status'=>'available', 'year'=>2019, 'capacity'=>6],
                    ['brand'=>'Toyota', 'type'=>'Hiace', 'plate_number'=>'DK 2222 BB', 'color'=>'Silver', 'status'=>'available', 'year'=>2018, 'capacity'=>14],
                    ['brand'=>'Mercedes', 'type'=>'Bus', 'plate_number'=>'DK 3333 CC', 'color'=>'Blue', 'status'=>'maintenance', 'year'=>2016, 'capacity'=>30],
                    ['brand'=>'Honda', 'type'=>'CRV', 'plate_number'=>'DK 4444 DD', 'color'=>'Black', 'status'=>'in_use', 'year'=>2020, 'capacity'=>4],
                ];
                foreach ($vehicles as $v) {
                    $vFiltered = $this->filterToTableColumns('vehicles', $v);
                    \App\Models\Vehicle::updateOrCreate(['plate_number' => $v['plate_number']], $vFiltered);
                }
            } else {
                $this->command->warn('Vehicle model or vehicles table not found — skipping vehicle seeding.');
            }

            // ORDERS
            if (class_exists(\App\Models\Order::class) && Schema::hasTable('orders')) {
                $this->command->info('Seeding orders...');
                $creator = null;
                if (class_exists(\App\Models\User::class)) {
                    $creator = \App\Models\User::whereIn('role', ['admin','super_admin'])->first();
                }
                $productIds = [];
                if (class_exists(\App\Models\Product::class)) {
                    $productIds = \App\Models\Product::pluck('id')->take(10)->toArray();
                }
                for ($i = 1; $i <= 15; $i++) {
                    $pickup = $now->copy()->addDays(rand(-10, 20))->setTime(rand(6,20), [0,15,30,45][array_rand([0,1,2,3])]);
                    $arrival = (clone $pickup)->addMinutes(rand(30, 480));
                    $orderData = [
                        'customer_name' => $faker->name(),
                        'email' => $faker->optional()->safeEmail(),
                        'phone' => $faker->phoneNumber(),
                        'pickup_time' => $pickup->toDateTimeString(),
                        'arrival_time' => $arrival->toDateTimeString(),
                        'estimated_duration_minutes' => max(30, (int) round($arrival->diffInMinutes($pickup))),
                        'passengers' => rand(1,10),
                        'pickup_location' => $faker->address(),
                        'destination' => $faker->city(),
                        'product_id' => $productIds ? $faker->randomElement($productIds) : null,
                        'adults' => rand(1,6),
                        'children' => rand(0,4),
                        'babies' => rand(0,2),
                        'vehicle_count' => rand(1,2),
                        'note' => $faker->optional()->sentence(),
                        'created_by' => $creator ? $creator->id : null,
                        'status' => ['pending','assigned','completed'][array_rand(['pending','assigned','completed'])],
                    ];
                    $orderFiltered = $this->filterToTableColumns('orders', $orderData);
                    \App\Models\Order::create($orderFiltered);
                }
            } else {
                $this->command->warn('Order model or orders table not found — skipping order seeding.');
            }

            // WORK SCHEDULES
            if (class_exists(\App\Models\WorkSchedule::class) && Schema::hasTable('work_schedules') && class_exists(\App\Models\User::class)) {
                $this->command->info('Seeding work schedules...');
                $driversAndGuides = \App\Models\User::whereIn('role', ['driver','guide'])->get();
                $currentYear = $now->year;
                $currentMonth = $now->month;
                foreach ($driversAndGuides as $u) {
                    $ws = [
                        'user_id' => $u->id,
                        'month' => $currentMonth,
                        'year' => $currentYear,
                        'total_hours' => $u->monthly_work_limit ?? 200,
                        'used_hours' => $u->used_hours ?? 0,
                    ];
                    $wsFiltered = $this->filterToTableColumns('work_schedules', $ws);
                    \App\Models\WorkSchedule::updateOrCreate(
                        ['user_id' => $u->id, 'month' => $currentMonth, 'year' => $currentYear],
                        $wsFiltered
                    );
                }
            } else {
                $this->command->warn('WorkSchedule model or work_schedules table not found — skipping work schedule seeding.');
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
