<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /** GET /api/admin/bookings?status=pending&date=2026-11-20 */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([
                Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED, Booking::STATUS_REJECTED,
                Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED,
            ])],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $bookings = Booking::with(['service:id,name,duration_minutes,icon', 'user:id,name,email,phone'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('booking_date', $date))
            ->orderByDesc('booking_date')
            ->orderBy('start_time')
            ->limit(500)
            ->get();

        return response()->json($bookings);
    }

    /** PATCH /api/admin/bookings/{booking}/status  { "status": "confirmed", "admin_note": "..." } */
    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $allowed = Booking::ADMIN_TRANSITIONS[$booking->status] ?? [];

        if (! in_array($data['status'], $allowed, true)) {
            return response()->json([
                'message' => "A {$booking->status} booking cannot be changed to {$data['status']}.",
            ], 422);
        }

        $booking->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? $booking->admin_note,
        ]);

        return response()->json(
            $booking->load(['service:id,name,duration_minutes,icon', 'user:id,name,email,phone']),
        );
    }
}
