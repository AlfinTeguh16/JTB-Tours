<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Main application routes. Dashboard is handled by DashboardController which
| selects the appropriate view based on authenticated user's role.
|
*/

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

/*
 * Authentication (public)
 */
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');

Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('register', [AuthController::class, 'register'])->name('register.post');

Route::post('logout', [AuthController::class, 'logout'])->name('logout');

/*
 * Dashboard (protected) - controller will choose view based on role
 */
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

/*
 * Protected routes (require authentication)
 */
Route::middleware(['auth'])->group(function () {

    /*
     * Users (super_admin only)
     */
    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class);
    });

    /*
     * Products (super_admin, admin)
     */
    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::resource('products', ProductController::class);
    });

    /*
     * Vehicles (super_admin, admin)
     */
    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::resource('vehicles', VehicleController::class);
    });

    /*
     * Orders (super_admin, admin)
     */
    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::resource('orders', OrderController::class);
    });

    /*
     * Assignments
     * - staff/admin/super_admin can manage assignments (list/create/store/destroy)
     * - driver & guide have /assignments/my and can change status
     * - show route allowed for many roles
     */
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::get('assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
        Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    });

    // driver & guide: their personal view + change status
    Route::middleware(['role:driver,guide'])->group(function () {
        Route::get('assignments/my', [AssignmentController::class, 'myAssignments'])->name('assignments.my');
        Route::post('assignments/{assignment}/status', [AssignmentController::class, 'changeStatus'])->name('assignments.changeStatus');
    });

    // show assignment details (allowed roles)
    Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])
        ->name('assignments.show')
        ->middleware('role:super_admin,admin,staff,driver,guide');

    /*
     * Availability
     */
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('availability', [AvailabilityController::class, 'index'])->name('availability.index');
        Route::post('availability/{user}/force', [AvailabilityController::class, 'forceChange'])->name('availability.force');
    });

    Route::middleware(['role:driver,guide'])->group(function () {
        Route::post('availability/toggle', [AvailabilityController::class, 'toggle'])->name('availability.toggle');
    });

    /*
     * Work schedules (super_admin, admin, staff)
     */
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('work-schedules', [WorkScheduleController::class, 'index'])->name('work-schedules.index');
        Route::post('work-schedules/generate', [WorkScheduleController::class, 'generateForAll'])->name('work-schedules.generate');
        Route::post('work-schedules/bulk', [WorkScheduleController::class, 'bulkUpdate'])->name('work-schedules.bulkUpdate');
        Route::post('work-schedules/reset', [WorkScheduleController::class, 'resetUsedHours'])->name('work-schedules.reset');

        Route::get('work-schedules/{workSchedule}/edit', [WorkScheduleController::class, 'edit'])->name('work-schedules.edit');
        Route::put('work-schedules/{workSchedule}', [WorkScheduleController::class, 'update'])->name('work-schedules.update');
    });

    /*
     * Reports & Exports (super_admin, admin)
     */
    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    });

});

/*
 * Health check
 */
Route::get('/ping', function () {
    return response('pong', 200);
});
