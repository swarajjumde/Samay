<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Admin (service owner) banane ka command
|--------------------------------------------------------------------------
| Chalane ka tareeka:
|     php artisan app:make-admin owner@example.com --name="Swaraj"
|
| Password terminal mein poocha jayega (type karte waqt dikhega nahi).
| Agar us email ka user pehle se hai to wahi admin ban jayega.
*/
Artisan::command('app:make-admin {email} {--name=Admin}', function () {
    $email = strtolower(trim((string) $this->argument('email')));

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Please give a valid email address.');

        return 1;
    }

    $password = (string) $this->secret('Admin password (minimum 8 characters)');

    if (strlen($password) < 8) {
        $this->error('Password must be at least 8 characters.');

        return 1;
    }

    if ($password !== (string) $this->secret('Type the password again')) {
        $this->error('Passwords do not match.');

        return 1;
    }

    $user = User::firstOrNew(['email' => $email]);
    $user->name = $user->exists ? $user->name : (string) $this->option('name');
    $user->password = $password;          // model ka 'hashed' cast isko hash kar deta hai
    $user->role = User::ROLE_ADMIN;
    $user->email_verified_at ??= now();
    $user->save();

    $this->info("Done. {$email} is now an admin. Login from the website with this email and password.");

    return 0;
})->purpose('Create an admin (service owner) or promote an existing user');
