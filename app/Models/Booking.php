<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_RETURNED  = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'lab_id',
        'equipment_id',
        'title',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: booking owner (user)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * Relationship: lab for this booking
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Relationship: equipment for this booking
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function qrTokens()
    {
        return $this->hasMany(QRToken::class);
    }

    /**
     * Relationship: QR tokens issued for this booking
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class);
    }

    /**
     * Relationship: usage logs tied to this booking
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    public function getIsActiveNowAttribute(): bool
    {
        $now = now();
        return $this->starts_at <= $now && $this->ends_at >= $now;
    }

    /**
     * Returns true when the booking window currently contains `now()`.
     *
     * @return bool
     */

    public function issueQrToken(int $ttlMinutes = 15): QRToken
    {
        return $this->qrTokens()->create([
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    /**
     * Create and return a new `QRToken` for this booking.
     *
     * The token is a UUID and expires after the provided TTL (minutes).
     *
     * @param int $ttlMinutes
     * @return \App\Models\QRToken
     */
}
