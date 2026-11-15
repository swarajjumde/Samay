<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Support\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Logged-in customer ki apni bookings. */
class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->bookings()
                ->with('service:id,name,duration_minutes,icon')
                ->orderByDesc('booking_date')
                ->orderByDesc('start_time')
                ->get(),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service = Service::findOrFail($data['service_id']);
        $user = $request->user();

        // Transaction + lock: slot check aur booking save ek saath hote hain,
        // taaki do customers ko ek hi slot na mil jaye.
        $booking = DB::transaction(function () use ($data, $service, $user) {
            $slot = collect(Availability::slotsFor($service, $data['date'], lock: true))
                ->firstWhere('time', $data['time']);

            if (! $slot || ! $slot['available']) {
                throw ValidationException::withMessages([
                    'time' => 'This time slot is not available. Please pick another one.',
                ]);
            }

            return $user->bookings()->create([
                'service_id' => $service->id,
                'booking_date' => $data['date'],
                'start_time' => $slot['time'],
                'end_time' => $slot['end'],
                'status' => Booking::STATUS_PENDING,
                'price' => $service->price,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json($booking->load('service:id,name,duration_minutes,icon'), 201);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        // Kisi aur ki booking ho to 404 (pata hi na chale ki wo exist karti hai).
        abort_unless((int) $booking->user_id === (int) $request->user()->id, 404);

        if (! in_array($booking->status, Booking::ACTIVE_STATUSES, true)) {
            return response()->json(['message' => 'This booking can no longer be cancelled.'], 422);
        }

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return response()->json($booking->load('service:id,name,duration_minutes,icon'));
    }
}
