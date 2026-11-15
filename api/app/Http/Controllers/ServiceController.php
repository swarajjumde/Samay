<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Support\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public endpoints: bina login ke bhi services aur slots dekh sakte hain. */
class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Service::where('is_active', true)->orderBy('name')->get(),
        );
    }

    public function show(Service $service): JsonResponse
    {
        abort_unless($service->is_active, 404);

        return response()->json($service);
    }

    /** GET /api/services/{service}/slots?date=2026-11-20 */
    public function slots(Request $request, Service $service): JsonResponse
    {
        abort_unless($service->is_active, 404);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'date' => $data['date'],
            'slots' => Availability::slotsFor($service, $data['date']),
        ]);
    }

    /** Landing page ke liye business ki basic jaankari. */
    public function business(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'open' => config('booking.open'),
            'close' => config('booking.close'),
            'closed_days' => config('booking.closed_days'),
            'max_days_ahead' => config('booking.max_days_ahead'),
        ]);
    }
}
