<?php

namespace App\Support;

use DateTimeImmutable;

/**
 * Ek din ke booking slots banata hai.
 *
 * Ye class jaan-boojh kar "pure PHP" hai (database ya framework ki zaroorat nahi),
 * taaki isko aasani se test kiya ja sake. Saara hisaab "minutes since midnight"
 * mein hota hai: 09:30 => 570.
 */
class SlotGenerator
{
    /**
     * @param  string  $date  'Y-m-d'
     * @param  int  $durationMinutes  service kitni der ki hai
     * @param  array<int, array{0: string, 1: string}>  $busy  pehle se booked intervals: [['10:00', '10:30'], ...]
     * @param  array{open: string, close: string, step: int, capacity: int, closed_days: array<int, int>, max_days_ahead: int}  $config
     * @param  DateTimeImmutable  $now  abhi ka time (app timezone mein)
     * @return array<int, array{time: string, end: string, available: bool}>
     */
    public static function generate(string $date, int $durationMinutes, array $busy, array $config, DateTimeImmutable $now): array
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $now->getTimezone());

        if ($day === false || $day->format('Y-m-d') !== $date || $durationMinutes <= 0) {
            return [];
        }

        $today = $now->setTime(0, 0);

        // Purani date, bahut aage ki date, ya chhutti ka din => koi slot nahi.
        if ($day < $today) {
            return [];
        }

        if ($day > $today->modify('+'.max(0, (int) $config['max_days_ahead']).' days')) {
            return [];
        }

        if (in_array((int) $day->format('w'), $config['closed_days'], true)) {
            return [];
        }

        $open = self::toMinutes($config['open']);
        $close = self::toMinutes($config['close']);
        $step = max(5, (int) $config['step']);
        $capacity = max(1, (int) $config['capacity']);

        $isToday = $day == $today;
        $nowMinutes = ((int) $now->format('G')) * 60 + (int) $now->format('i');

        $busyMinutes = array_map(
            fn (array $interval) => [self::toMinutes($interval[0]), self::toMinutes($interval[1])],
            $busy,
        );

        $slots = [];

        for ($start = $open; $start + $durationMinutes <= $close; $start += $step) {
            $end = $start + $durationMinutes;

            // Kitni bookings is slot se takra rahi hain?
            $overlaps = 0;
            foreach ($busyMinutes as [$busyStart, $busyEnd]) {
                if ($busyStart < $end && $busyEnd > $start) {
                    $overlaps++;
                }
            }

            $slots[] = [
                'time' => self::toTime($start),
                'end' => self::toTime($end),
                'available' => $overlaps < $capacity && (! $isToday || $start > $nowMinutes),
            ];
        }

        return $slots;
    }

    /** '09:30' ya '09:30:00' => 570 */
    public static function toMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }

    /** 570 => '09:30' */
    public static function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
