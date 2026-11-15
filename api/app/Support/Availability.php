<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Service;

/**
 * Database se us din ki bookings uthata hai aur SlotGenerator ko deta hai.
 */
class Availability
{
    /**
     * @return array<int, array{time: string, end: string, available: bool}>
     */
    public static function slotsFor(Service $service, string $date, bool $lock = false): array
    {
        $query = Booking::query()
            ->whereDate('booking_date', $date)
            ->whereIn('status', Booking::ACTIVE_STATUSES);

        // Booking banate waqt rows lock karte hain, taaki do log ek hi slot
        // ek saath na le sakein (yeh DB transaction ke andar chalta hai).
        if ($lock) {
            $query->lockForUpdate();
        }

        $busy = $query->get(['id', 'start_time', 'end_time'])
            ->map(fn (Booking $booking) => [$booking->start_time, $booking->end_time])
            ->all();

        return SlotGenerator::generate(
            $date,
            $service->duration_minutes,
            $busy,
            config('booking'),
            now()->toDateTimeImmutable(),
        );
    }
}
