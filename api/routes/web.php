<?php

use Illuminate\Support\Facades\Route;

// Ye project sirf API hai. Website (landing + panel) ../web folder mein Vue se banti hai.
Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'message' => 'API is running. See /api/services',
]));
