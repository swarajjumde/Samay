<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'service_id', 'booking_date', 'start_time', 'end_time', 'status', 'price', 'notes', 'admin_note'])]
class Booking extends Model
{
    public const STATUS_PENDING = 'pending';      // customer ne book kiya, admin ne abhi dekha nahi

    public const STATUS_CONFIRMED = 'confirmed';  // admin ne haan kar di

    public const STATUS_REJECTED = 'rejected';    // admin ne mana kar diya

    public const STATUS_COMPLETED = 'completed';  // kaam ho gaya

    public const STATUS_CANCELLED = 'cancelled';  // customer ya admin ne cancel kiya

    /** Ye status wali bookings slot ko "bhara hua" rakhti hain. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED];

    /** Admin kis status se kis status pe ja sakta hai. */
    public const ADMIN_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_REJECTED],
        self::STATUS_CONFIRMED => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date:Y-m-d',
            'price' => 'float',
        ];
    }

    /** Database '10:00:00' deta hai, API mein hum hamesha '10:00' bhejte hain. */
    protected function startTime(): Attribute
    {
        return Attribute::make(get: fn (?string $value) => $value === null ? null : substr($value, 0, 5));
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(get: fn (?string $value) => $value === null ? null : substr($value, 0, 5));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
