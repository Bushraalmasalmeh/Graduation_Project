<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use App\Models\UsageSession;
use App\Models\Charger;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HardwareController extends Controller
{
    /**
     * الخطوة 1: التحقق من وجود حجز (عند إدخال الرقم الوظيفي في الكيباد)
     */
    public function verifyJob(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
            'uid' => 'required|string', // UID الشاحن (مثلاً 911)
        ]);

        $now = Carbon::now('Asia/Amman');

        // البحث عن حجز "Pending" مرتبط بالشاحن الصحيح عبر الـ UID والمستخدم عبر الرقم الوظيفي
        $booking = Booking::with('user')
            ->where('status', 'pending')
            ->whereHas('charger', function ($q) use ($request) {
                $q->where('uid', $request->uid);
            })
            ->whereHas('user', function ($q) use ($request) {
                $q->where('job_number', $request->job_number);
            })
            ->first();

        if (!$booking) {
            return response()->json(['status' => 'error', 'code' => 'NO_BOOKING']);
        }

        // فحص النوافذ الزمنية للتأكد أن الحجز فعال الآن
        $start = Carbon::parse($booking->start_time)->setTimezone('Asia/Amman');
        $end = Carbon::parse($booking->end_time)->setTimezone('Asia/Amman');

        if ($now->lt($start)) {
            return response()->json([
                'status' => 'error',
                'code' => 'TOO_EARLY',
                'starts_at' => $start->format('H:i')
            ]);
        }

        if ($now->gt($end)) {
            return response()->json(['status' => 'error', 'code' => 'EXPIRED']);
        }

        return response()->json([
            'status' => 'success',
            'user_name' => $booking->user->name
        ]);
    }

    /**
     * الخطوة 2: بدء الجلسة وفتح الريلاي (عند تأكيد المستخدم بالضغط على #)
     */
    public function startSession(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
            'uid'        => 'required|string'
        ]);

        $now = Carbon::now('Asia/Amman');
        $user = User::where('job_number', $request->job_number)->first();

        if (!$user) return response()->json(['message' => 'USER_NOT_FOUND'], 404);

        // جلب الحجز المعلق المرتبط بالشاحن والمستخدم
        $booking = Booking::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('charger', function ($q) use ($request) {
                $q->where('uid', $request->uid);
            })
            ->first();

        if (!$booking) return response()->json(['message' => 'NO_BOOKING_FOUND'], 404);

        return DB::transaction(function () use ($booking, $user, $request, $now) {
            // تحديث حالة الحجز إلى "نشط"
            $booking->update([
                'status' => 'active',
                'actual_start_time' => $now
            ]);

            // تحديث حالة الشاحن ليصبح مشغولاً
            $charger = Charger::where('uid', $request->uid)->first();
            if ($charger) {
                $charger->update(['status' => 'busy']);
            }

            // إنشاء سجل جلسة الشحن
            $session = UsageSession::create([
                'booking_id'    => $booking->id,
                'user_id'       => $user->id,
                'charger_id'    => $charger?->id,
                'status'        => 'active',
                'session_start' => $now,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'START_CONFIRMED',
                'user_name' => $user->name,
                'activate_charging' => true
            ]);
        });
    }

    /**
     * الخطوة 3: إنهاء الجلسة وإغلاق الريلاي (آمن - يتطلب الرقم الوظيفي)
     */
    public function stopSession(Request $request)
    {
        // تم إضافة job_number لضمان أن صاحب الحجز هو من يقوم بالإيقاف
        $request->validate([
            'uid' => 'required|string',
            'job_number' => 'required|string'
        ]);

        $now = Carbon::now('Asia/Amman');

        return DB::transaction(function () use ($request, $now) {
            // البحث عن الحجز النشط المرتبط بهذا الشاحن "و" هذا الرقم الوظيفي
            $booking = Booking::whereHas('charger', function ($q) use ($request) {
                $q->where('uid', $request->uid);
            })
                ->whereHas('user', function ($q) use ($request) {
                    $q->where('job_number', $request->job_number);
                })
                ->where('status', 'active')
                ->first();

            // إذا لم يتطابق الرقم الوظيفي مع الحجز النشط على هذا الشاحن
            if (!$booking) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'UNAUTHORIZED_OR_NO_SESSION'
                ], 403);
            }

            // جلب الجلسة النشطة لإنهائها وحساب المدة
            $session = UsageSession::where('booking_id', $booking->id)
                ->where('status', 'active')
                ->first();

            if ($session) {
                $duration = $now->diffInMinutes(Carbon::parse($session->session_start));
                $session->update([
                    'session_end' => $now,
                    'duration'    => $duration,
                    'status'      => 'completed'
                ]);
            }

            // تحديث حالة الحجز والشاحن
            $booking->update([
                'status' => 'completed',
                'actual_end_time' => $now
            ]);

            $charger = Charger::where('uid', $request->uid)->first();
            if ($charger) {
                $charger->update(['status' => 'available']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'SESSION_STOPPED',
                'duration_minutes' => $duration ?? 0
            ]);
        });
    }
}
