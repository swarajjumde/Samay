<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('pending');
            $table->decimal('price', 10, 2)->default(0);   // booking ke waqt ka price (baad mein service ka price badle to bhi ye na badle)
            $table->text('notes')->nullable();              // customer ka note
            $table->text('admin_note')->nullable();         // admin ka jawab
            $table->timestamps();

            $table->index(['booking_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
