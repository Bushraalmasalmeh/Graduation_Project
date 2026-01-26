<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChargerStation;
use App\Models\Charger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StationController extends Controller
{
    /**
     * عرض جميع المحطات مع الكبائن والشواحن التابعة لها
     */
    public function index()
    {
        $stations = ChargerStation::with('cabinets.chargers')->get();

        return response()->json([
            'stations' => $stations
        ]);
    }

    /**
     * عرض تفاصيل محطة معينة
     */
    public function show($id)
    {
        $station = ChargerStation::with('cabinets.chargers')->findOrFail($id);

        return response()->json([
            'station' => $station
        ]);
    }

    /**
     * جلب المواعيد المتاحة للمستخدم (تطبيق الفلاتر)
     * تم التعديل لربط المواعيد بشاحن متاح فعلياً
     */
    public function getUserAvailability(Request $request, $stationId)
    {
        // 1. جلب المحطة والتأكد من وجود شاحن واحد متاح على الأقل حالياً
        $station = ChargerStation::findOrFail($stationId);

        // البحث عن أول شاحن حالته 'available' داخل هذه المحطة
        $firstCharger = Charger::whereHas('cabinet', function($query) use ($stationId) {
            $query->where('station_id', $stationId);
        })->where('status', 'available')->first();

        // إذا لم يوجد أي شاحن متاح في المحطة حالياً
        if (!$firstCharger) {
            return response()->json([
                'success' => false,
                'message' => 'No chargers are currently available at this station.',
                'data' => []
            ], 200); // نرسل 200 مع مصفوفة فارغة لكي لا ينهار التطبيق
        }

        // 2. إعدادات الوقت
        $duration = floatval($request->query('duration', 1)); // المدة بالساعات (الافتراضي 1)
        $slotMinutes = (int) ($duration * 60);
        $startHour = 7; // بداية الدوام 7 صباحاً
        $endHour = 20;  // نهاية الدوام 8 مساءً
        $today = Carbon::now('Asia/Amman')->startOfDay();

        $slots = [];

        // 3. حلقة توليد المواعيد (كل نصف ساعة خطوة)
        for ($hour = $startHour; $hour <= $endHour - $duration; $hour += 0.5) {
            $from = $today->copy()->addMinutes($hour * 60);
            $to = $from->copy()->addMinutes($slotMinutes);

            // 4. التحقق من وجود حجز يتعارض مع هذا الوقت لهذا الشاحن تحديداً
            $isBooked = Booking::where('charger_id', $firstCharger->id)
                ->whereIn('status', ['pending', 'active', 'confirmed'])
                ->where(function ($query) use ($from, $to) {
                    $query->where(function ($q) use ($from, $to) {
                        $q->whereBetween('start_time', [$from, $to])
                          ->orWhereBetween('end_time', [$from, $to]);
                    })->orWhere(function ($q) use ($from, $to) {
                        $q->where('start_time', '<=', $from)
                          ->where('end_time', '>=', $to);
                    });
                })
                ->exists();

            // 5. بناء كائن الموعد بالبيانات التي يطلبها الفلاتر
            $slots[] = [
                'from'       => $from->toIso8601String(),
                'to'         => $to->toIso8601String(),
                'status'     => $isBooked ? 'busy' : 'available',
                'charger_id' => $firstCharger->id,      // المعرف الرقمي للشاحن
                'station_id' => (int)$stationId,        // معرف المحطة
                'uid'        => $firstCharger->uid,     // الـ UID الخاص بالهاردوير (مثل 911)
            ];
        }

        // 6. إرجاع النتيجة (نستخدم المفتاح 'data' ليتوافق مع كود Flutter الحالي)
        return response()->json([
            'success' => true,
            'data'    => $slots,
        ]);
    }
}
