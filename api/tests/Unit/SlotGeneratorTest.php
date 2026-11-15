<?php

namespace Tests\Unit;

use App\Support\SlotGenerator;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class SlotGeneratorTest extends TestCase
{
    /** @return array<string, mixed> */
    private function config(array $overrides = []): array
    {
        return array_merge([
            'open' => '09:00',
            'close' => '12:00',
            'step' => 30,
            'capacity' => 1,
            'closed_days' => [],
            'max_days_ahead' => 30,
        ], $overrides);
    }

    private function now(string $time = '2026-11-16 08:00'): DateTimeImmutable
    {
        return new DateTimeImmutable($time, new DateTimeZone('Asia/Kolkata'));
    }

    public function test_it_builds_slots_between_open_and_close(): void
    {
        $slots = SlotGenerator::generate('2026-11-17', 60, [], $this->config(), $this->now());

        // 09:00 se 11:00 tak (11:00 + 60 min = 12:00 = close), har 30 minute pe
        $this->assertSame(['09:00', '09:30', '10:00', '10:30', '11:00'], array_column($slots, 'time'));
        $this->assertSame('10:00', $slots[0]['end']);
        $this->assertNotContains(false, array_column($slots, 'available'));
    }

    public function test_booked_time_blocks_overlapping_slots(): void
    {
        $slots = SlotGenerator::generate('2026-11-17', 60, [['10:00', '11:00']], $this->config(), $this->now());
        $available = array_column($slots, 'available', 'time');

        $this->assertTrue($available['09:00']);    // 09:00-10:00 takrata nahi
        $this->assertFalse($available['09:30']);   // 09:30-10:30 takrata hai
        $this->assertFalse($available['10:00']);
        $this->assertFalse($available['10:30']);
        $this->assertTrue($available['11:00']);    // 11:00-12:00 takrata nahi
    }

    public function test_capacity_allows_parallel_bookings(): void
    {
        $slots = SlotGenerator::generate('2026-11-17', 60, [['10:00', '11:00']], $this->config(['capacity' => 2]), $this->now());

        $this->assertTrue(array_column($slots, 'available', 'time')['10:00']);
    }

    public function test_past_times_today_are_not_available(): void
    {
        $slots = SlotGenerator::generate('2026-11-16', 30, [], $this->config(), $this->now('2026-11-16 10:10'));
        $available = array_column($slots, 'available', 'time');

        $this->assertFalse($available['09:00']);
        $this->assertFalse($available['10:00']);
        $this->assertTrue($available['10:30']);
    }

    public function test_no_slots_for_past_closed_or_far_dates(): void
    {
        $this->assertSame([], SlotGenerator::generate('2026-11-15', 30, [], $this->config(), $this->now()));                       // kal
        $this->assertSame([], SlotGenerator::generate('2026-11-22', 30, [], $this->config(['closed_days' => [0]]), $this->now())); // Sunday band
        $this->assertSame([], SlotGenerator::generate('2027-03-01', 30, [], $this->config(), $this->now()));                       // bahut aage
        $this->assertSame([], SlotGenerator::generate('not-a-date', 30, [], $this->config(), $this->now()));
    }
}
