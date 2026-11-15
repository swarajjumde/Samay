<?php

/*
|--------------------------------------------------------------------------
| Booking settings
|--------------------------------------------------------------------------
| Business ke kaam ke ghante aur slot ke niyam. Inko .env se badal sakte ho,
| code chhedne ki zaroorat nahi. Badalne ke baad: php artisan config:cache
*/

return [

    // Dukaan khulne aur band hone ka time (24 ghante wala format)
    'open' => env('BOOKING_OPEN', '09:00'),
    'close' => env('BOOKING_CLOSE', '18:00'),

    // Har kitne minute pe ek naya slot shuru ho sakta hai
    'step' => (int) env('BOOKING_STEP_MINUTES', 30),

    // Ek hi time pe kitni bookings le sakte ho (1 = ek waqt mein ek hi customer)
    'capacity' => (int) env('BOOKING_CAPACITY', 1),

    // Chhutti ke din: 0 = Sunday, 1 = Monday ... 6 = Saturday. Example: "0" ya "0,6"
    'closed_days' => array_values(array_map(
        'intval',
        array_filter(explode(',', (string) env('BOOKING_CLOSED_DAYS', '')), fn ($day) => $day !== ''),
    )),

    // Aaj se kitne din aage tak booking le sakte ho
    'max_days_ahead' => (int) env('BOOKING_MAX_DAYS_AHEAD', 30),

];
