<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateBookingRequest;
use App\Http\Requests\Api\CancelBookingRequest;
use App\Jobs\SendSessionStartReminder;
use App\Models\Booking;
use App\Models\Cabinet;
use App\Models\Charger;
use App\Models\ChargerStation;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * جلب قائمة حجوزات المستخدم
     */
    public function index()
    {
        $user = request()->user();
        $bookings = Booking::with(['station', 'cabinet', 'charger'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['bookings' => $bookings]);
    }
public function store(CreateBookingRequest $request)
{
    $startTime = Carbon::parse($request->start_time, 'Asia/Amman');
    $now = Carbon::now('Asia/Amman');

    return DB::transaction(function () use ($request, $startTime, $now) {
        $user = $request->user();

        // 1. التحقق من تاريخ اليوم
        if (!$startTime->isSameDay($now)) {
            return response()->json([
                'message' => 'Booking must be for today only.',
                'code' => 'INVALID_BOOKING_DATE'
            ], 422);
        }

        // 2. التحقق من نافذة الـ 30 دقيقة مع هامش خطأ بسيط (5 دقائق)
        $diffInMinutes = $now->diffInMinutes($startTime, false);
        if ($diffInMinutes < -5 || $diffInMinutes > 30) {
            return response()->json([
                'message' => 'Booking must be within the next 30 minutes.',
                'code' => 'TOO_EARLY'
            ], 422);
        }

        // 3. منع تكرار الحجز في نفس اليوم
        $alreadyBooked = Booking::where('user_id', $user->id)
            ->whereDate('start_time', $now->toDateString())
            ->whereIn('status', ['pending', 'active', 'confirmed'])
            ->lockForUpdate()
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'message' => 'You already have a booking today.',
                'code' => 'DAILY_LIMIT_REACHED'
            ], 422);
        }

        // 4. جلب الشاحن المحدد والتأكد من توافره
        $charger = Charger::with('cabinet.station')
            ->where('id', $request->charger_id)
            ->lockForUpdate()
            ->first();

        if (!$charger || $charger->status !== 'available') {
            return response()->json([
                'message' => 'Selected charger is not available.'
            ], 422);
        }

        // 5. إنشاء سجل الحجز
        $booking = Booking::create([
            'user_id'    => $user->id,
            'station_id' => $charger->cabinet->station_id,
            'cabinet_id' => $charger->cabinet_id,
            'charger_id' => $charger->id,
            'UID'        => $charger->uid,
            'start_time' => $startTime,
            'end_time'   => $startTime->copy()->addMinutes($request->duration_minutes),
            'duration'   => $request->duration_minutes,
            'status'     => 'pending',
        ]);

        // 6. تحديث حالة الشاحن فوراً
        $charger->update(['status' => 'busy']);

        // 7. إرسال تذكير قبل الجلسة بـ 30 دقيقة
        if ($booking->start_time) {
            SendSessionStartReminder::dispatch($booking->id)
                ->delay($booking->start_time->subMinutes(30));
        }

        // 8. إشعار المستخدم
        try {
            $this->notificationService->notifyUser(
                $booking->user_id,
                'Booking Confirmed',
                'Your charging slot is booked.',
                'booking'
            );
        } catch (\Exception $e) {
            Log::error("Notification error: " . $e->getMessage());
        }

        return response()->json([
            "message"     => "booking created successfully",
            "booking_id"  => $booking->id,
            "cabinet_id"  => $booking->cabinet_id,
            "charger_id"  => $booking->charger_id,
            "user_id"     => $booking->user_id,
            "start_time"  => $booking->start_time->copy()->setTimezone('Asia/Amman')->toIso8601String(),
            "duration"    => $booking->duration,
            "status"      => $booking->status,
            "updated_at"  => $booking->updated_at->toIso8601String(),
            "created_at"  => $booking->created_at->toIso8601String(),
        ], 200);
    });
}
    public function cancel(CancelBookingRequest $request)
    {
        $user = $request->user();
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
            Charger::where('id', $booking->charger_id)->update(['status' => 'available']);
            return response()->json(['message' => 'Booking cancelled successfully.'], 200);
        });
    }

    /**
     * إيقاف الجلسة من خلال تطبيق الموبايل
     */
    public function stopMobile(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:bookings,id']);
        $booking = Booking::with('charger')->find($request->booking_id);

        if (!$booking || $booking->status !== 'active') {
            return response()->json(['message' => 'No active session found.'], 422);
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'completed']);
            $booking->charger->update(['status' => 'available']);
            return response()->json(['message' => 'Charging stopped successfully.'], 200);
        });
    }

    /**
     * جلب المواعيد المتاحة لشاحن معين (يستخدمه الفلاتر)
     */
    public function getSchedule(Request $request)
    {
        $request->validate(['uid' => 'required|string', 'date' => 'nullable|date']);
        
        $uid = $request->uid;
        $date = $request->date ?? now()->toDateString();
        $charger = Charger::where('uid', $uid)->with('cabinet')->firstOrFail();

        // توليد المواعيد للساعات من 8 صباحاً حتى 8 مساءً
        $slots = collect(range(8, 20))->map(function ($hour) use ($date, $charger) {
            return [
                'from'       => Carbon::parse("$date $hour:00:00")->toIso8601String(),
                'to'         => Carbon::parse("$date $hour:59:59")->toIso8601String(),
                'status'     => 'available',
                'charger_id' => $charger->id,
                'station_id' => $charger->cabinet->station_id,
                'uid'        => $charger->uid,
            ];
        });

        // وضع علامة "محجوز" على المواعيد المتعارضة
        $booked = Booking::where('UID', $uid)->whereDate('start_time', $date)
            ->whereIn('status', ['pending', 'active', 'confirmed'])->get();

        foreach ($slots as &$slot) {
            foreach ($booked as $booking) {
                if (Carbon::parse($slot['from'])->between($booking->start_time, $booking->end_time)) {
                    $slot['status'] = 'booked';
                    break;
                }
            }
        }

        return response()->json(['schedule' => $slots]);
    }
}
