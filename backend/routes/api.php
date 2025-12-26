<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\HardwareController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SessionHistoryController;
use App\Http\Controllers\Api\StopSessionController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminStationController;
use App\Http\Controllers\Api\Admin\AdminCabinetController;
use App\Http\Controllers\Api\Admin\AdminChargerController;
use App\Http\Controllers\Api\Admin\AdminBookingController;
use App\Http\Controllers\Api\Admin\AdminMessageController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\NotificationController as ControllersNotificationController;

// ==================== PUBLIC ROUTES ====================

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/stations', [StationController::class, 'index']);
Route::get('/stations/{id}', [StationController::class, 'show']);

// ==================== HARDWARE ROUTES ====================

Route::middleware(['hardware.auth'])->prefix('hardware')->group(function () {
    Route::post('/start-session', [HardwareController::class, 'startSession']);
    Route::post('/stop-session', [HardwareController::class, 'stopSession']);
});

// ==================== USER ROUTES ====================

Route::middleware(['auth:sanctum', 'check.user.status'])->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/profile/update', [UserController::class, 'update']);
    Route::post('/profile/avatar', [UserController::class, 'updateAvatar']);
    Route::post('/profile/car', [UserController::class, 'updateCar']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/cancel', [BookingController::class, 'cancel']);

    // Sessions
    Route::post('/session/stop', [StopSessionController::class, 'stop']);
    Route::get('/session/history', [SessionHistoryController::class, 'index']);

    // Notifications



    // Contact
    Route::post('/contact', [ContactController::class, 'store']);
    Route::get('/contact/my', [ContactController::class, 'myMessages']);
});

// ==================== ADMIN ROUTES ====================

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'overview']);

    // Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // Stations
    Route::get('/stations', [AdminStationController::class, 'index']);
    Route::post('/stations', [AdminStationController::class, 'store']);
    Route::put('/stations/{id}', [AdminStationController::class, 'update']);
    Route::delete('/stations/{id}', [AdminStationController::class, 'destroy']);

    // Cabinets
    Route::get('/cabinets', [AdminCabinetController::class, 'index']);
    Route::post('/cabinets', [AdminCabinetController::class, 'store']);
    Route::put('/cabinets/{id}', [AdminCabinetController::class, 'update']);
    Route::delete('/cabinets/{id}', [AdminCabinetController::class, 'destroy']);

    // Chargers
    Route::get('/chargers', [AdminChargerController::class, 'index']);
    Route::post('/chargers', [AdminChargerController::class, 'store']);
    Route::put('/chargers/{id}', [AdminChargerController::class, 'update']);
    Route::delete('/chargers/{id}', [AdminChargerController::class, 'destroy']);

    // Bookings
    Route::get('/bookings', [AdminBookingController::class, 'index']);
    Route::post('/bookings', [AdminBookingController::class, 'store']);

    // Messages
    Route::get('/messages', [AdminMessageController::class, 'index']);
    Route::post('/messages/{id}/reply', [AdminMessageController::class, 'reply']);

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'show']);
    Route::put('/settings', [AdminSettingsController::class, 'update']);

    // Notifications
    /*
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/notifications/send', [AdminNotificationController::class, 'store']);
    */
});
