<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetMonthlyWorkSchedules extends Command
{
    protected $signature = 'schedules:reset-monthly';
    protected $description = 'Reset used_hours to 0 and prepare new schedules for the new month';

    public function handle()
    {
        $now = now();
        $year = $now->year;
        $month = $now->month;

        $this->info("Memulai reset jadwal kerja bulan {$month}/{$year}...");

        $users = User::whereIn('role', ['driver', 'guide'])->get();

        DB::transaction(function () use ($users, $year, $month) {
            foreach ($users as $user) {

                $schedule = WorkSchedule::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'year'    => $year,
                        'month'   => $month,
                    ],
                    [
                        'total_hours' => $user->monthly_work_limit ?? 200,
                        'used_hours'  => 0, 
                    ]
                );
            }
        });

        $this->info('Reset bulanan selesai: semua used_hours = 0 untuk bulan ini.');
    }
}