<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
*/

/*
 * Root route - Always redirect to login
 */
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

/*
 * Authentication routes (public)
 */
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');

Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('register', [AuthController::class, 'register'])->name('register.post');

Route::post('logout', [AuthController::class, 'logout'])->name('logout');

/*
 * Dashboard route (protected)
 * Hanya bisa diakses setelah login
 */
Route::get('/dashboard', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

    // Redirect berdasarkan role
    if (in_array($user->role, ['driver','guide'])) {
        return redirect()->route('assignments.my');
    }

    if (in_array($user->role, ['admin','super_admin'])) {
        return redirect()->route('orders.index');
    }

    if ($user->role === 'staff') {
        return redirect()->route('assignments.index');
    }

    // Fallback
    return redirect()->route('orders.index');
})->name('dashboard')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Protected Routes (semua membutuhkan authentication)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    
    /*
     * Users management (super_admin only)
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
     */
    // staff/admin/super_admin CRUD for assignments
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('assignments', [AssignmentController::class,'index'])->name('assignments.index');
        Route::get('assignments/create', [AssignmentController::class,'create'])->name('assignments.create');
        Route::post('assignments', [AssignmentController::class,'store'])->name('assignments.store');
        Route::delete('assignments/{assignment}', [AssignmentController::class,'destroy'])->name('assignments.destroy');
    });

    // driver & guide: their view + change status
    Route::middleware(['role:driver,guide'])->group(function () {
        Route::get('assignments/my', [AssignmentController::class,'myAssignments'])->name('assignments.my');
        Route::post('assignments/{assignment}/status', [AssignmentController::class,'changeStatus'])->name('assignments.changeStatus');
    });

    // allow show for authorized roles
    Route::get('assignments/{assignment}', [AssignmentController::class,'show'])
        ->name('assignments.show')
        ->middleware('role:super_admin,admin,staff,driver,guide');

    /*
     * Availability
     */
    // admin/staff/super_admin view & force-change
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('availability', [AvailabilityController::class,'index'])->name('availability.index');
        Route::post('availability/{user}/force', [AvailabilityController::class,'forceChange'])->name('availability.force');
    });

    // driver/guide toggle self
    Route::middleware(['role:driver,guide'])->group(function () {
        Route::post('availability/toggle', [AvailabilityController::class,'toggle'])->name('availability.toggle');
    });

    /*
     * Work Schedules (super_admin, admin, staff)
     */
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('work-schedules', [WorkScheduleController::class,'index'])->name('work-schedules.index');
        Route::post('work-schedules/generate', [WorkScheduleController::class,'generateForAll'])->name('work-schedules.generate');
        Route::post('work-schedules/bulk', [WorkScheduleController::class,'bulkUpdate'])->name('work-schedules.bulkUpdate');
        Route::post('work-schedules/reset', [WorkScheduleController::class,'resetUsedHours'])->name('work-schedules.reset');

        Route::get('work-schedules/{workSchedule}/edit', [WorkScheduleController::class,'edit'])->name('work-schedules.edit');
        Route::put('work-schedules/{workSchedule}', [WorkScheduleController::class,'update'])->name('work-schedules.update');
    });

    /*
     * Reports & Exports (super_admin, admin)
     */
    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::get('reports', [ReportController::class,'index'])->name('reports.index');
        Route::get('reports/export/excel', [ReportController::class,'exportExcel'])->name('reports.export.excel');
        Route::get('reports/export/pdf', [ReportController::class,'exportPdf'])->name('reports.export.pdf');
        Route::get('reports/{report}', [ReportController::class,'show'])->name('reports.show');
    });

});

/*
|--------------------------------------------------------------------------
| Public routes (tidak membutuhkan authentication)
|--------------------------------------------------------------------------
*/
Route::get('/ping', function () { 
    return response('pong', 200); 
});