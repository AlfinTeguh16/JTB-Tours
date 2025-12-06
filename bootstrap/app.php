<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) { // ← ini pengganti Kernel.php
        // Jalankan reset tiap bulan, hari ke-1 jam 00:05
        $schedule->command('schedules:reset-monthly')
                 ->monthlyOn(1, '00:05');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
