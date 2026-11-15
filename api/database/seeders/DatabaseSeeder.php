<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Shuruaat ke liye kuch sample services. Admin panel se inko badal ya hata sakte ho.
     * Dobara chalane par duplicate nahi banenge (firstOrCreate).
     *
     * Admin user yahan NAHI banta, uske liye:  php artisan app:make-admin you@example.com
     */
    public function run(): void
    {
        $services = [
            ['name' => 'Quick Consultation', 'description' => 'A short one-to-one session to understand what you need.', 'duration_minutes' => 30, 'price' => 299, 'icon' => 'pi pi-comments'],
            ['name' => 'Standard Service', 'description' => 'Our most booked service. Covers everything most customers need.', 'duration_minutes' => 60, 'price' => 599, 'icon' => 'pi pi-star'],
            ['name' => 'Premium Service', 'description' => 'Extra time and extra attention for bigger jobs.', 'duration_minutes' => 90, 'price' => 999, 'icon' => 'pi pi-crown'],
            ['name' => 'Home Visit', 'description' => 'We come to your place at the time you choose.', 'duration_minutes' => 120, 'price' => 1499, 'icon' => 'pi pi-home'],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['name' => $service['name']], $service);
        }
    }
}
