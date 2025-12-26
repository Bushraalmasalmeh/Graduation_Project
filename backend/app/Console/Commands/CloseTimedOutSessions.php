<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UsageSession;
use App\Models\Booking;
use App\Models\Charger;
use Carbon\Carbon;

class CloseTimedOutSessions extends Command
{
    // اسم الأمر اللي استعملناه في bootstrap/app.php
    protected $signature = 'sessions:close-timeouts';

    protected $description = 'Automatically close charging sessions that exceeded their booking end time';

    public function handle()
    {
        $now = now();

        // 1) نجيب كل الجلسات النشطة اللي لسا شغالة
        //    ووقت الحجز تبعها خلص
        $sessions = UsageSession::where('status', 'active')
            ->whereNull('session_end')
            ->whereHas('booking', function ($q) use ($now) {
                $q->where('end_time', '<=', $now);
            })
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('No timed-out sessions found.');
            return 0;
        }

        foreach ($sessions as $session) {
            $booking = $session->booking;
            $charger = $session->charger;

            // 2) نحدد وقت النهاية: منطقياً هو end_time تبع الحجز
            $endTime = $booking ? $booking->end_time : $now;

            // نحسب المدة بالدقائق
            $duration = $session->session_start->diffInMinutes($endTime);

            // 3) نحدّث الجلسة كـ timeout
            $session->session_end       = $endTime;
            $session->duration_minutes  = $duration;
            $session->status            = 'timeout';
            $session->save();

            // 4) نحدّث الحجز كـ timeout برضه
            if ($booking) {
                $booking->status           = 'timeout';
                $booking->duration_minutes = $duration;
                $booking->save();
            }

            // 5) نرجّع الشاحن لحالته المتاحة
            if ($charger && $charger->status === 'busy') {
                $charger->status = 'available';
                $charger->save();
            }
        }

        $this->info('Closed ' . $sessions->count() . ' timed-out sessions.');

        return 0;
    }
}
