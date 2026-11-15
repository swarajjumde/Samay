<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = now()->toDateString();
        $weekEnd = now()->addDays(6)->toDateString();

        // Agle 7 din mein har din kitni bookings hain (chart ke liye).
        $perDay = Booking::query()
            ->whereDate('booking_date', '>=', $today)
            ->whereDate('booking_date', '<=', $weekEnd)
            ->whereIn('status', Booking::ACTIVE_STATUSES)
            ->get(['id', 'booking_date'])
            ->groupBy(fn (Booking $booking) => $booking->booking_date->format('Y-m-d'))
            ->map->count();

        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->toDateString();
            $week[] = ['date' => $date, 'count' => $perDay[$date] ?? 0];
        }

        return response()->json([
            'stats' => [
                'total_bookings' => Booking::count(),
                'pending' => Booking::where('status', Booking::STATUS_PENDING)->count(),
                'today' => Booking::whereDate('booking_date', $today)->whereIn('status', Booking::ACTIVE_STATUSES)->count(),
                'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
                'revenue' => (float) Booking::where('status', Booking::STATUS_COMPLETED)->sum('price'),
            ],
            'week' => $week,
            'upcoming' => Booking::with(['service:id,name,icon', 'user:id,name,phone'])
                ->whereDate('booking_date', '>=', $today)
                ->whereIn('status', Booking::ACTIVE_STATUSES)
                ->orderBy('booking_date')
                ->orderBy('start_time')
                ->limit(6)
                ->get(),
        ]);
    }
}
