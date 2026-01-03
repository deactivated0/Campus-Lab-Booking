<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard without using a closure so routes can be cached
Route::redirect('/', '/dashboard');

// Google OAuth (Socialite)
Route::get('/auth/google/redirect', [AuthController::class, 'redirectGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'callbackGoogle'])->name('auth.google.callback');

// Kiosk (can be public for demo)
Route::get('/kiosk', [KioskController::class, 'page'])->name('kiosk.page');
Route::post('/kiosk/scan', [KioskController::class, 'scan'])->name('kiosk.scan');
Route::get('/kiosk/scan-url/{token}', [KioskController::class, 'scanUrl'])->name('kiosk.scan-url');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/approvals', [BookingController::class, 'approvals'])->middleware('role:Admin|LabStaff')->name('approvals.index');
    Route::get('/bookings/events', [BookingController::class, 'events'])->name('bookings.events');
    // Availability endpoint to get equipment unavailable for a time range
    Route::get('/bookings/availability', [BookingController::class, 'availability'])->name('bookings.availability');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking}/issue-qr', [BookingController::class, 'issueQr'])->name('bookings.issueQr');
    Route::get('/bookings/{booking}/latest-qr', [BookingController::class, 'latestQr'])->name('bookings.latestQr');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');

    // Equipment (Admin/Staff management, students can view)
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
    Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
    Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');
    Route::post('/equipment/reorder', [EquipmentController::class, 'reorder'])->name('equipment.reorder');

    // Labs (Admin only)
    Route::get('/labs', [LabController::class, 'index'])->middleware('role:Admin')->name('labs.index');
    Route::post('/labs', [LabController::class, 'store'])->middleware('role:Admin')->name('labs.store');
    Route::put('/labs/{lab}', [LabController::class, 'update'])->middleware('role:Admin')->name('labs.update');
    Route::delete('/labs/{lab}', [LabController::class, 'destroy'])->middleware('role:Admin')->name('labs.destroy');

    // Reports (Admin/Staff)
    Route::get('/reports', [ReportController::class, 'index'])->middleware('role:Admin|LabStaff')->name('reports.index');
    Route::get('/reports/data', [ReportController::class, 'data'])->middleware('role:Admin|LabStaff')->name('reports.data');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->middleware('role:Admin|LabStaff')->name('reports.export.csv');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->middleware('role:Admin|LabStaff')->name('reports.export.pdf');
    // Delete single usage log
    Route::delete('/reports/logs/{log}', [ReportController::class, 'destroy'])->middleware('role:Admin|LabStaff')->name('reports.logs.destroy');

    // Admin user management
    Route::prefix('admin')->name('admin.')->middleware('role:Admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [\App\Http\Controllers\Admin\UserController::class, 'updateRole'])->name('users.update.role');
    });
});

require __DIR__.'/auth.php';

// Development-only status endpoint to check DB/Redis/queue health
if (config('app.debug')) {
    Route::get('/status', [App\Http\Controllers\StatusController::class, 'index'])->name('status');
}
