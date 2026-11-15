<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'booking.open' => '09:00',
            'booking.close' => '18:00',
            'booking.step' => 30,
            'booking.capacity' => 1,
            'booking.closed_days' => [],
            'booking.max_days_ahead' => 30,
        ]);

        $this->service = Service::create([
            'name' => 'Test Service',
            'duration_minutes' => 60,
            'price' => 500,
        ]);

        $this->date = now()->addDays(2)->toDateString();
    }

    public function test_services_and_slots_are_public(): void
    {
        $this->getJson('/api/services')->assertOk()->assertJsonCount(1);

        $this->getJson("/api/services/{$this->service->id}/slots?date={$this->date}")
            ->assertOk()
            ->assertJsonPath('slots.0.time', '09:00')
            ->assertJsonPath('slots.0.available', true);
    }

    public function test_a_customer_can_book_a_free_slot(): void
    {
        $customer = User::factory()->create();
        Sanctum::actingAs($customer);

        $this->postJson('/api/bookings', [
            'service_id' => $this->service->id,
            'date' => $this->date,
            'time' => '10:00',
            'notes' => 'Please be on time',
        ])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('start_time', '10:00')
            ->assertJsonPath('end_time', '11:00');

        $this->getJson('/api/bookings')->assertOk()->assertJsonCount(1);
    }

    public function test_the_same_slot_cannot_be_booked_twice(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $payload = ['service_id' => $this->service->id, 'date' => $this->date, 'time' => '10:00'];

        $this->postJson('/api/bookings', $payload)->assertCreated();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/bookings', $payload)->assertStatus(422);
        $this->postJson('/api/bookings', [...$payload, 'time' => '10:30'])->assertStatus(422);   // overlap
        $this->postJson('/api/bookings', [...$payload, 'time' => '11:00'])->assertCreated();     // free

        $this->getJson("/api/services/{$this->service->id}/slots?date={$this->date}")
            ->assertJsonPath('slots.2.time', '10:00')
            ->assertJsonPath('slots.2.available', false);
    }

    public function test_a_customer_can_cancel_only_their_own_booking(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $booking = $this->makeBooking($owner);

        Sanctum::actingAs($other);
        $this->patchJson("/api/bookings/{$booking->id}/cancel")->assertNotFound();

        Sanctum::actingAs($owner);
        $this->patchJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    }

    public function test_customers_cannot_open_admin_routes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/dashboard')->assertForbidden();
        $this->getJson('/api/admin/bookings')->assertForbidden();
        $this->postJson('/api/admin/services', [])->assertForbidden();
    }

    public function test_admin_can_manage_bookings_and_services(): void
    {
        $booking = $this->makeBooking(User::factory()->create());
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonCount(7, 'week');

        $this->getJson('/api/admin/bookings?status=pending')->assertOk()->assertJsonCount(1);

        $this->patchJson("/api/admin/bookings/{$booking->id}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('status', 'confirmed');

        // confirmed se wapas pending nahi ho sakta
        $this->patchJson("/api/admin/bookings/{$booking->id}/status", ['status' => 'pending'])
            ->assertStatus(422);

        $this->patchJson("/api/admin/bookings/{$booking->id}/status", ['status' => 'completed'])
            ->assertOk();

        $serviceId = $this->postJson('/api/admin/services', [
            'name' => 'New Service',
            'duration_minutes' => 45,
            'price' => 250,
        ])->assertCreated()->json('id');

        $this->putJson("/api/admin/services/{$serviceId}", [
            'name' => 'Renamed Service',
            'duration_minutes' => 45,
            'price' => 300,
            'is_active' => false,
        ])->assertOk()->assertJsonPath('is_active', false);

        $this->deleteJson("/api/admin/services/{$serviceId}")->assertOk();

        // jis service ki booking hai woh delete nahi hoti
        $this->deleteJson("/api/admin/services/{$this->service->id}")->assertStatus(422);

        $this->getJson('/api/admin/customers')->assertOk()->assertJsonCount(1);
    }

    private function makeBooking(User $user): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'service_id' => $this->service->id,
            'booking_date' => $this->date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => Booking::STATUS_PENDING,
            'price' => 500,
        ]);
    }
}
