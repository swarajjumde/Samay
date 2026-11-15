<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /** Admin ko band (inactive) services bhi dikhti hain. */
    public function index(): JsonResponse
    {
        return response()->json(
            Service::withCount('bookings')->orderBy('name')->get(),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $service = Service::create($this->validated($request));

        return response()->json($service, 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $service->update($this->validated($request));

        return response()->json($service);
    }

    public function destroy(Service $service): JsonResponse
    {
        // Jis service ki bookings ho chuki hain usse delete nahi karte (history kharab ho jayegi).
        if ($service->bookings()->exists()) {
            return response()->json([
                'message' => 'This service already has bookings. Turn it off instead of deleting it.',
            ], 422);
        }

        $service->delete();

        return response()->json(['message' => 'Service deleted.']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
