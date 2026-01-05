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
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\NotificationController as ControllersNotificationController;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetCodeMail;
// ==================== PUBLIC ROUTES ====================
// USER AUTH ROUTES
Route::post('/user/register', [AuthController::class, 'userRegister']);
Route::post('/user/login', [AuthController::class, 'userLogin']);
//ADMIN AUTH ROUTES
Route::post('/admin/register', [AuthController::class, 'adminRegister']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
// COMMON AUTH ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
// PASSWORD RESET ROUTES
Route::post('/forgotPassword', [AuthController::class, 'forgotPassword']);
Route::post('/verifyCode', [AuthController::class, 'verifyCode']);
Route::post('/resetPassword', [AuthController::class, 'resetPassword']);
//STATIONS
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
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/avatar', [UserController::class, 'updateAvatar']);
    Route::post('/profile/car', [UserController::class, 'updateCar']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings/create', [BookingController::class, 'store']);
    Route::post('/bookings/cancel', [BookingController::class, 'cancel']);
    Route::post('/logout-all', [UserController::class, 'logoutAll']);
    Route::get('/stations/schedule', [BookingController::class, 'getSchedule']);
    // Sessions
    Route::post('/session/stop', [StopSessionController::class, 'stop']);
    Route::get('/session/history', [SessionHistoryController::class, 'index']);

    // Notifications

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/test', [NotificationController::class, 'test']);

    Route::get('/stations/{stationId}/availability', [StationController::class, 'getAvailability']);
    Route::post('/bookings/create', [BookingController::class, 'create'])->middleware('auth:sanctum');


    // Contact
    Route::post('/contact', [ContactController::class, 'store']);
    Route::get('/contact/my', [ContactController::class, 'myMessages']);
});

// ==================== ADMIN ROUTES ====================
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/me', [AuthController::class, 'adminProfile']);

    Route::post('/update-profile', [ProfileController::class, 'updateProfile']);
    Route::post('/change-password', [ProfileController::class, 'changePassword']);
    Route::get('/dashboard', [AdminDashboardController::class, 'overview']);
    Route::get('/reports/summary', [AdminDashboardController::class, 'reportSummary']);
    Route::get('/stations/{id}/schedule', [AdminStationController::class, 'getStationSchedule']);

    // Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/create', [AdminUserController::class, 'store']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // Stations
    Route::get('/stations', [AdminStationController::class, 'index']);
    Route::post('/stations/create', [AdminStationController::class, 'store']);
    Route::put('/stations/{id}', [AdminStationController::class, 'update']);
    Route::delete('/stations/{id}', [AdminStationController::class, 'destroy']);
    Route::delete('/bookings/{id}', [AdminStationController::class, 'deleteBooking']);

    // Chargers
    Route::get('/chargers', [AdminChargerController::class, 'index']);
    Route::post('/chargers', [AdminChargerController::class, 'store']);
    Route::put('/chargers/{id}', [AdminChargerController::class, 'update']);
    Route::delete('/chargers/{id}', [AdminChargerController::class, 'destroy']);

    // Bookings
    Route::get('/bookings', [AdminBookingController::class, 'index']);
    Route::post('/bookings', [AdminBookingController::class, 'store']);
    Route::put('/bookings/{id}/cancel', [AdminBookingController::class, 'cancel']);

    // Messages
    Route::get('/messages', [AdminMessageController::class, 'index']);
    Route::post('/messages/{id}/reply', [AdminMessageController::class, 'reply']);
    Route::put('/messages/{id}/resolve', [AdminMessageController::class, 'resolve']);

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'show']);
    Route::put('/settings', [AdminSettingsController::class, 'update']);

    // Notifications
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/notifications/send', [AdminNotificationController::class, 'store']);
    Route::post('/notifications/broadcast', [AdminNotificationController::class, 'broadcast']);
});
Route::get('/test-email', function () {
    $code = rand(100000, 999999);
    Mail::to('islamabukhalaf5@gmail.com')->send(new ResetCodeMail($code));
    return 'Email sent!';
});
Route::get('/test/notify/{method}', function ($method) {
    $session = \App\Models\UsageSession::latest()->first();
    $userId = $session->user_id ?? 1;
    $chargerId = $session->charger_id ?? 'CHG-001';
    $startTime = $session->session_start ?? now();
    $service = app(NotificationService::class);
    switch ($method) {
        case 'charging-started':
            $service->chargingStarted($session);
            break;
        case 'charging-stopped':
            $service->chargingStopped($session);
            break;
        case 'will-start':
            $service->sessionWillStart($userId, $chargerId, $startTime);
            break;
        case 'will-end':
            $service->sessionWillEnd($session);
            break;
        case 'auto-cancel':
            $service->sessionAutoCancelled($session);
            break;
        case 'user-cancel':
            $service->sessionCancelledByUser($session);
            break;
        case 'notify-user':
            $service->notifyUser($userId, 'Test Title', 'This is a test message', 'test');
            break;
        case 'notify-admins':
            $service->notifyAdmins('Admin Alert', 'This is for all admins', 'alert');
            break;
        default:
            return response()->json(['error' => 'Invalid method'], 400);
    }
    return response()->json(['status' => 'Notification sent using ' . $method]);
});
