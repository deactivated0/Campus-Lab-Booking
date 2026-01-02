<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRToken extends Model
{
    use HasFactory;

    protected $table = 'qr_tokens';

    protected $fillable = [
        'booking_id',
        'token',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relationship: the booking this QR token belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function getIsValidAttribute(): bool
    {
        if ($this->used_at) return false;
        return $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Returns true when the token is not used and its `expires_at` is in the future.
     *
     * @return bool
     */
}
