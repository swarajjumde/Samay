<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            User::select(['id', 'name', 'email', 'phone', 'created_at'])
                ->where('role', User::ROLE_CUSTOMER)
                ->withCount('bookings')
                ->latest()
                ->limit(500)
                ->get(),
        );
    }
}
